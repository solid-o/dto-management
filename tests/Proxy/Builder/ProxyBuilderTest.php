<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Builder;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use PHPUnit\Framework\TestCase;
use ProxyManager\Exception\InvalidProxiedClassException;
use Solido\DtoManagement\Exception\FinalMethodException;
use Solido\DtoManagement\Exception\MethodAlreadyDeclaredException;
use Solido\DtoManagement\Exception\NonExistentInterfaceException;
use Solido\DtoManagement\Exception\NonExistentMethodException;
use Solido\DtoManagement\Exception\NonExistentPropertyException;
use Solido\DtoManagement\Exception\PropertyAlreadyDeclaredException;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\DtoManagement\Tests\Fixtures\FinalClass;
use Solido\DtoManagement\Tests\Fixtures\Model\Interfaces\UserInterface;
use Solido\DtoManagement\Tests\Fixtures\ProxableClass;

class ProxyBuilderTest extends TestCase
{
    public function testShouldThrowIfClassCannotBeProxied(): void
    {
        $this->expectException(InvalidProxiedClassException::class);
        new ProxyBuilder(new \ReflectionClass(FinalClass::class));
    }

    public function testShouldExposeProperties(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        self::assertEquals(
            ['publicProperty', "\0*\0protectedProperty", "\0".ProxableClass::class."\0privateProperty"],
            \array_keys($builder->properties->getInstanceProperties())
        );

        self::assertEquals(
            ['publicProperty', "\0*\0protectedProperty"],
            \array_keys($builder->properties->getAccessibleProperties())
        );
    }

    public function testAnInterfaceCouldBeAddedToProxy(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addInterface(UserInterface::class);

        self::assertEquals([ProxyInterface::class, UserInterface::class], $builder->getInterfaces());
    }

    public function testShouldThrowTryingToAddNonExistentInterface(): void
    {
        $this->expectException(NonExistentInterfaceException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addInterface(\stdClass::class);
    }

    public function testShouldAddInterceptorToAProperty(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addPropertyInterceptor('publicProperty', new Interceptor(''));

        self::assertCount(1, $builder->getPropertyInterceptors('publicProperty'));
    }

    public function testShouldAddInterceptorToAMethod(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethodInterceptor('publicMethod', new Interceptor(''));

        self::assertCount(1, $builder->getMethodInterceptors('publicMethod'));
    }

    public function testShouldAddWrapperToAMethod(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethodWrapper('publicMethod', new Wrapper('try {', "} finally { echo \"CIAO\"; }"));

        self::assertCount(1, $builder->getMethodWrappers('publicMethod'));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAPrivateProperty(): void
    {
        $this->expectException(NonExistentPropertyException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addPropertyInterceptor('privateProperty', new Interceptor(''));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAPrivateMethod(): void
    {
        $this->expectException(NonExistentMethodException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethodInterceptor('privateMethod', new Interceptor(''));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAFinalMethod(): void
    {
        $this->expectException(FinalMethodException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethodInterceptor('finalMethod', new Interceptor(''));
    }

    public function testPropertyCanBeAddedToTheProxy(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addProperty(new PropertyGenerator('customProperty', null, PropertyGenerator::FLAG_PUBLIC), '$this->customProperty = 42');
        $builder->addProperty(new PropertyGenerator('privateProperty', null, PropertyGenerator::FLAG_PRIVATE), '');

        self::assertCount(2, $builder->getExtraProperties());
        self::assertEquals("\$this->customProperty = 42;\n", $builder->getConstructorCode());
    }

    public function testMethodCanBeAddedToTheProxy(): void
    {
        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('customMethod', [], PropertyGenerator::FLAG_PUBLIC));
        $builder->addMethod(new MethodGenerator('privateMethod', [], PropertyGenerator::FLAG_PRIVATE));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));

        self::assertCount(3, $builder->getExtraMethods());
    }

    public function testShouldThrowIfPropertyHasBeenAlreadyDeclared(): void
    {
        $this->expectException(PropertyAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addProperty(new PropertyGenerator('publicProperty', null, PropertyGenerator::FLAG_PUBLIC), '');
    }

    public function testShouldThrowIfMethodHasBeenAlreadyDeclared(): void
    {
        $this->expectException(MethodAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));
    }

    public function testShouldThrowTryingToAddAMagicMethod(): void
    {
        $this->expectException(MethodAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new \ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('__set', [], PropertyGenerator::FLAG_PUBLIC));
    }
}
