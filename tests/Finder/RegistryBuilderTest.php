<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Finder;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument as ProphecyArgument;
use Prophecy\PhpUnit\ProphecyTrait;
use Solido\DtoManagement\Exception\RuntimeException;
use Solido\DtoManagement\Finder\ArgumentResolver\Argument;
use Solido\DtoManagement\Finder\ArgumentResolver\ArgumentValueResolverInterface;
use Solido\DtoManagement\Finder\RegistryBuilder;
use Solido\DtoManagement\Tests\Fixtures;

class RegistryBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testInterfaceExclusion(): void
    {
        $builder = new RegistryBuilder(Fixtures\SemVerModel::class);
        $builder->excludeInterface(Fixtures\SemVerModel\Interfaces\UserInterface::class);
        $registry = $builder->build();

        self::assertFalse($registry->has(Fixtures\SemVerModel\Interfaces\UserInterface::class));
        self::assertTrue($registry->has(Fixtures\SemVerModel\Interfaces\FooInterface::class));

        $locator = $registry->get(Fixtures\SemVerModel\Interfaces\FooInterface::class);
        self::assertEquals(['1.2'], $locator->getVersions());
    }

    public function testShouldNotCollectDTOsWithWrongNamespacePattern(): void
    {
        $builder = new RegistryBuilder(Fixtures\SemVerModel::class);
        $registry = $builder->build();

        self::assertTrue($registry->has(Fixtures\SemVerModel\Interfaces\UserInterface::class));
        self::assertFalse($registry->has(Fixtures\ServicedModel\Interfaces\UserInterface::class));
        self::assertFalse($registry->has(Fixtures\Model\Interfaces\UserInterface::class));

        $locator = $registry->get(Fixtures\SemVerModel\Interfaces\UserInterface::class);
        self::assertEquals(['1.0', '1.1', '1.2', '2.0.alpha.1'], $locator->getVersions());
    }

    public function testUnresolvableServicesShouldCauseAnExceptionToBeThrownWhenOnResolving(): void
    {
        $builder = new RegistryBuilder(Fixtures\ServicedModel::class);
        $registry = $builder->build();

        $locator = $registry->get(Fixtures\ServicedModel\Interfaces\FooInterface::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"Solido\DtoManagement\Tests\Fixtures\ServicedModel\v1\v1_0\Foo::__construct()" requires that you provide a value for the "$user" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.');
        $locator->get('1.0');
    }

    public function testDTOsWithNullableAndDefaultArgumentsShouldBeCreatedCorrectly(): void
    {
        $builder = new RegistryBuilder(Fixtures\ServicedModel::class);
        $registry = $builder->build();

        $locator = $registry->get(Fixtures\ServicedModel\Interfaces\FooInterface::class);

        $user = $locator->get('1.1');
        self::assertEquals('test', $user->user);

        $user = $locator->get('1.2');
        self::assertNull($user->user);
    }

    public function testShouldAddCustomArgumentValueResolver(): void
    {
        $resolver = $this->prophesize(ArgumentValueResolverInterface::class);
        $builder = new RegistryBuilder(Fixtures\ServicedModel::class);
        $builder->withArgumentValueResolver($resolver->reveal());

        $registry = $builder->build();

        $token = ProphecyArgument::that(static function (Argument $arg): bool {
            return $arg->getParameterType()->getName() === Fixtures\ServicedModel\v1\v1_0\User::class;
        });
        $resolver->supports($token)->shouldBeCalled()->willReturn(true);
        $resolver->resolve($token)->shouldBeCalled()->willReturn([new Fixtures\ServicedModel\v1\v1_0\User(new \stdClass(), new Fixtures\DefinedService())]);

        $locator = $registry->get(Fixtures\ServicedModel\Interfaces\FooInterface::class);
        $locator->get('1.0');
    }
}
