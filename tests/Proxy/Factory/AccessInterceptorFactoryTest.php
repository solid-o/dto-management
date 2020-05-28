<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Factory;

use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Proxy\Factory\Configuration;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\DtoManagement\Tests\Fixtures\SemVerModel\Interfaces\UserInterface;
use Solido\DtoManagement\Tests\Fixtures\SemVerModel\v2\v2_0_alpha_1\User;
use function array_values;
use function Safe\class_implements;
use function is_subclass_of;

class AccessInterceptorFactoryTest extends TestCase
{
    public function testShouldNotGenerateProxyIfNoExtensionIsPresent(): void
    {
        $configuration = new Configuration();
        $configuration->setGeneratorStrategy(new EvaluatingGeneratorStrategy());
        $configuration->setProxiesNamespace('__TMP__\\Solido\\Test1');

        $factory = new AccessInterceptorFactory($configuration);
        $className = $factory->generateProxy(User::class);

        self::assertEquals(User::class, $className);
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
}
