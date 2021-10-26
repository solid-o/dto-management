<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Finder\ArgumentResolver;

use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionType;
use Solido\DtoManagement\Finder\ArgumentResolver\Argument;
use Solido\DtoManagement\Finder\ArgumentResolver\ServiceContainerArgumentValueResolver;
use PHPUnit\Framework\TestCase;

class ServiceContainerArgumentValueResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|ContainerInterface
     */
    private ObjectProphecy $container;
    private ServiceContainerArgumentValueResolver $resolver;

    protected function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->resolver = new ServiceContainerArgumentValueResolver($this->container->reveal());
    }

    public function testSupportsShouldReturnFalseIfTypeIsNotReflectionNamedType(): void
    {
        $type = $this->prophesize(ReflectionType::class);
        $argument = new Argument(__CLASS__, 'param', $type->reveal(), true, null, false, true);
        self::assertFalse($this->resolver->supports($argument));
    }

    public function testSupportsShouldReturnFalseIfTypeIsBuiltin(): void
    {
        $type = $this->prophesize(ReflectionNamedType::class);
        $type->isBuiltin()->willReturn(true);

        $argument = new Argument(__CLASS__, 'param', $type->reveal(), true, null, false, true);
        self::assertFalse($this->resolver->supports($argument));
    }

    public function testSupportsShouldReturnFalseIfContainerDoesNotHaveTheService(): void
    {
        $type = $this->prophesize(ReflectionNamedType::class);
        $type->isBuiltin()->willReturn(false);
        $type->getName()->willReturn(__CLASS__);

        $this->container->has(__CLASS__)
            ->shouldBeCalled()
            ->willReturn(false);

        $argument = new Argument(__CLASS__, 'param', $type->reveal(), true, null, false, true);
        self::assertFalse($this->resolver->supports($argument));
    }
}
