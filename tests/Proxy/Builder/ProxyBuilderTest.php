<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Builder;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use PHPUnit\Framework\TestCase;
use ProxyManager\Exception\InvalidProxiedClassException;
use ReflectionClass;
use Solido\DtoManagement\Exception\FinalMethodException;
use Solido\DtoManagement\Exception\MethodAlreadyDeclaredException;
use Solido\DtoManagement\Exception\NonExistentInterfaceException;
use Solido\DtoManagement\Exception\NonExistentMethodException;
use Solido\DtoManagement\Exception\NonExistentPropertyException;
use Solido\DtoManagement\Exception\PropertyAlreadyDeclaredException;
use Solido\DtoManagement\Exception\TraitAlreadyAddedException;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\DtoManagement\Tests\Fixtures\BarInterface;
use Solido\DtoManagement\Tests\Fixtures\FinalClass;
use Solido\DtoManagement\Tests\Fixtures\Model\Interfaces\UserInterface;
use Solido\DtoManagement\Tests\Fixtures\ProxableClass;
use Solido\DtoManagement\Tests\Fixtures\ReadOnlyClass;
use Solido\DtoManagement\Tests\Fixtures\UserTrait;

class ProxyBuilderTest extends TestCase
{
    public function testShouldThrowIfClassCannotBeProxied(): void
    {
        $this->expectException(InvalidProxiedClassException::class);
        new ProxyBuilder(new ReflectionClass(FinalClass::class));
    }

    /**
     * @requires PHP >= 8.2
     */
    public function testShouldThrowIfClassIsReadonlyAndCannotBeProxied(): void
    {
        $this->expectException(InvalidProxiedClassException::class);
        new ProxyBuilder(new ReflectionClass(ReadOnlyClass::class));
    }

    public function testShouldThrowOnInterfaces(): void
    {
        $this->expectException(InvalidProxiedClassException::class);
        new ProxyBuilder(new ReflectionClass(BarInterface::class));
    }

    public function testShouldExposeProperties(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
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
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addInterface(UserInterface::class);

        self::assertEquals([ProxyInterface::class, UserInterface::class], $builder->getInterfaces());
    }

    public function testATraitCouldBeAddedToProxy(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addTrait(UserTrait::class);

        self::assertEquals([UserTrait::class], array_keys($builder->getTraits()));
    }

    public function testATraitCouldNotBeAddedTwice(): void
    {
        $this->expectException(TraitAlreadyAddedException::class);
        $this->expectExceptionMessage('Trait "'. UserTrait::class . '" has been already added for proxy of class '.ProxableClass::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addTrait(UserTrait::class);
        $builder->addTrait(UserTrait::class);
    }

    public function testShouldThrowTryingToAddNonExistentInterface(): void
    {
        $this->expectException(NonExistentInterfaceException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addInterface(\stdClass::class);
    }

    public function testShouldAddInterceptorToAProperty(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        self::assertTrue($builder->hasProperty('publicProperty'));
        self::assertTrue($builder->hasProperty('protectedProperty'));

        $builder->addPropertyInterceptor('publicProperty', new Interceptor(''));
        $builder->addPropertyInterceptor('protectedProperty', new Interceptor(''));

        self::assertCount(1, $builder->getPropertyInterceptors('publicProperty'));
        self::assertCount(1, $builder->getPropertyInterceptors('protectedProperty'));
    }

    public function testShouldAddInterceptorToAMethod(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        self::assertTrue($builder->hasMethod('publicMethod'));

        $builder->addMethodInterceptor('publicMethod', new Interceptor(''));
        self::assertCount(1, $builder->getMethodInterceptors('publicMethod'));
    }

    public function testShouldAddWrapperToAMethod(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethodWrapper('publicMethod', new Wrapper('try {', "} finally { echo \"CIAO\"; }"));

        self::assertCount(1, $builder->getMethodWrappers('publicMethod'));
    }

    public function testShouldThrowTryingAddingAWrapperToANonExistingMethod(): void
    {
        $this->expectException(FinalMethodException::class);
        $this->expectExceptionMessage('Method "finalMethod" is final on class ' . ProxableClass::class . ' and cannot be wrapped');

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethodWrapper('finalMethod', new Wrapper('try {', "} finally { echo \"CIAO\"; }"));
    }

    public function testShouldThrowTryingAddingAWrapperToAFinalMethod(): void
    {
        $this->expectException(NonExistentMethodException::class);
        $this->expectExceptionMessage('Method "noMethod" is non-existent or not accessible on class ' . ProxableClass::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethodWrapper('noMethod', new Wrapper('try {', "} finally { echo \"CIAO\"; }"));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAPrivateProperty(): void
    {
        $this->expectException(NonExistentPropertyException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addPropertyInterceptor('privateProperty', new Interceptor(''));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAPrivateMethod(): void
    {
        $this->expectException(NonExistentMethodException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethodInterceptor('privateMethod', new Interceptor(''));
    }

    public function testShouldThrowIfTryingToAddAnInterceptorToAFinalMethod(): void
    {
        $this->expectException(FinalMethodException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethodInterceptor('finalMethod', new Interceptor(''));
    }

    public function testPropertyCanBeAddedToTheProxy(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addProperty(new PropertyGenerator('customProperty', null, PropertyGenerator::FLAG_PUBLIC), '$this->customProperty = 42');
        $builder->addProperty(new PropertyGenerator('privateProperty', null, PropertyGenerator::FLAG_PRIVATE), '');

        self::assertCount(2, $builder->getExtraProperties());
        self::assertEquals("\$this->customProperty = 42;\n", $builder->getConstructorCode());
    }

    public function testMethodCanBeAddedToTheProxy(): void
    {
        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('customMethod', [], PropertyGenerator::FLAG_PUBLIC));
        $builder->addMethod(new MethodGenerator('privateMethod', [], PropertyGenerator::FLAG_PRIVATE));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));

        self::assertCount(3, $builder->getExtraMethods());
    }

    public function testShouldThrowIfPropertyHasBeenAlreadyDeclared(): void
    {
        $this->expectException(PropertyAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addProperty(new PropertyGenerator('publicProperty', null, PropertyGenerator::FLAG_PUBLIC), '');
    }

    public function testShouldThrowIfMethodHasBeenAlreadyDeclared(): void
    {
        $this->expectException(MethodAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));
        $builder->addMethod(new MethodGenerator('publicMethod', [], PropertyGenerator::FLAG_PUBLIC));
    }

    public function testShouldThrowTryingToAddAMagicMethod(): void
    {
        $this->expectException(MethodAlreadyDeclaredException::class);

        $builder = new ProxyBuilder(new ReflectionClass(ProxableClass::class));
        $builder->addMethod(new MethodGenerator('__set', [], PropertyGenerator::FLAG_PUBLIC));
    }
}
