<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Factory;

use Laminas\Code\Generator\PropertyGenerator;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ReflectionClass;
use ReflectionMethod;
use Solido\DtoManagement\Exception\EmptyBuilderException;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Proxy\Factory\Configuration;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\DtoManagement\Tests\Fixtures\Foo2Trait;
use Solido\DtoManagement\Tests\Fixtures\FooTrait;
use Solido\DtoManagement\Tests\Fixtures\Php8ProxableClass;
use Solido\DtoManagement\Tests\Fixtures\ProxableClass;
use Solido\DtoManagement\Tests\Fixtures\SemVerModel\Interfaces\UserInterface;
use Solido\DtoManagement\Tests\Fixtures\SemVerModel\v2\v2_0_alpha_1\User;

use function array_values;
use function Safe\class_implements;
use function is_subclass_of;

class AccessInterceptorFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testShouldNotGenerateProxyIfNoExtensionIsPresent(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test1');

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        self::assertEquals(User::class, $className);
    }

    public function testShouldUseCustomGenerator(): void
    {
        $configuration = new Configuration();
        $generator = $this->prophesize(ProxyGeneratorInterface::class);
        $generator->generate(Argument::cetera())->shouldBeCalled();

        $factory = new AccessInterceptorFactory($configuration);
        $factory->setGenerator($generator->reveal());
        $factory->generateProxy(User::class);
    }

    public function testShouldThrowIfNoExtensionIsPresentAndThrowEmptyOptionIsTrue(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test1');

        $this->expectException(EmptyBuilderException::class);
        $this->expectExceptionMessage('Proxy builder is empty, aborting proxy generation.');

        $factory = new AccessInterceptorFactory($configuration);
        $factory->generateProxy(User::class, ['throw_empty' => true]);
    }

    public function testShouldGenerateWithSetterInterceptors(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test2');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addPropertyInterceptor('barBar', new Interceptor('$value = \'INTERCEPTED: \'.$value;'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        $obj->barBar = 42;

        self::assertTrue(isset($obj->barBar));
        self::assertEquals('INTERCEPTED: 42', $obj->barBar);
        self::assertTrue(is_subclass_of($className, User::class));
        self::assertEquals([UserInterface::class, ProxyInterface::class], array_values(class_implements($className)));
    }

    public function testShouldGenerateWithMethodInterceptors(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test3');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethodInterceptor('setFoo', new Interceptor('$value = \'INTERCEPTED: \'.$value;'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        $obj->setFoo(42);

        self::assertEquals('INTERCEPTED: 42', $obj->foo);
    }

    public function testShouldNotThrowCompileErrorOnMethodReturningNull(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test4');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethodInterceptor('returningVoid', new Interceptor('// Do nothing, just call the parent'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        $obj->returningVoid();

        self::assertEquals('void_called', $obj->foo);
    }

    /**
     * @requires PHP >= 8.0
     */
    public function testShouldCorrectlyGenerateProxyAgainstClassWithUnionTypes(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test5');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addPropertyInterceptor('unionProperty', new Interceptor('// Do nothing, just call the parent'));
                $proxyBuilder->addMethodInterceptor('unionTypedMethod', new Interceptor('// Do nothing, just call the parent'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(Php8ProxableClass::class);

        $reflector = new ReflectionClass($className);
        $type = $reflector->getProperty('unionProperty')->getType();
        self::assertInstanceOf(\ReflectionUnionType::class, $type);

        $type = $reflector->getMethod('unionTypedMethod')->getReturnType();
        self::assertInstanceOf(\ReflectionUnionType::class, $type);
    }

    public function testShouldGenerateWithAddedTrait(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test6');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addTrait(Foo2Trait::class);
                $proxyBuilder->addTrait(FooTrait::class, [
                    ['method' => 'FooTrait::fooMethod', 'alias' => 'newMethod', 'visibility' => ReflectionMethod::IS_PUBLIC],
                    ['method' => 'FooTrait::foo2Method', 'alias' => 'barMethod'],
                ], [
                    ['method' => 'FooTrait::methodToReplace', 'traitToReplace' => 'Foo2Trait']
                ]);

                $proxyBuilder->addMethodInterceptor('setFoo', new Interceptor('$value = $this->methodToReplace();'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        $obj->setFoo(42);
        self::assertEquals($className, $obj->foo);
        self::assertEquals('Solido\DtoManagement\Tests\Fixtures\FooTrait::fooMethod', $obj->newMethod());
    }

    public function testShouldAddExtraProperty(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test7');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addProperty(new PropertyGenerator('extraProp', 42));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        self::assertSame(42, $obj->extraProp);
    }

    public function testShouldGenerateWithMethodWrapper(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test8');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethodWrapper('setFoo', new Wrapper('try { $value = \'Before: \'.$value;', '} finally { $this->foo = $value . \'. After\'; }'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        $obj = new $className();
        $obj->setFoo(42);

        self::assertEquals('Before: 42. After', $obj->foo);
    }

    public function testShouldCorrectlyGeneratePublicPropertiesProxy(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test9');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addPropertyInterceptor('protectedProperty', new Interceptor('// Do nothing, just call the parent'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(ProxableClass::class);

        $obj = new $className();

        self::assertNull($obj->publicProperty);
        $obj->publicProperty = 42;
        self::assertTrue(isset($obj->publicProperty));
    }

    public function testShouldCorrectlyHandleVariadicParams(): void
    {
        $this->expectNotToPerformAssertions();

        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test10');
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethodInterceptor('publicWithVariadic', new Interceptor('// Do nothing, just call the parent'));
            }
        });

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(ProxableClass::class);

        $obj = new $className();
        $obj->publicWithVariadic('foo', 'bar', 'barbar');
    }
}
