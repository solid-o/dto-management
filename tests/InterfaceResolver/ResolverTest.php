<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\InterfaceResolver;

use Prophecy\PhpUnit\ProphecyTrait;
use Solido\DtoManagement\Finder\ServiceLocator;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\InterfaceResolver\Resolver;
use PHPUnit\Framework\TestCase;

class ResolverTest extends TestCase
{
    use ProphecyTrait;

    public function testGetShouldCallCorrectLocator(): void
    {
        $registry = $this->prophesize(ServiceLocatorRegistry::class);
        $registry->get('Interface')->willReturn($locator = $this->prophesize(ServiceLocator::class));
        $locator->get('latest')->willReturn($obj = new \stdClass());

        $resolver = new Resolver($registry->reveal());
        self::assertSame($obj, $resolver->resolve('Interface'));
    }

    public function testHasShouldForwardCallToRegistry(): void
    {
        $registry = $this->prophesize(ServiceLocatorRegistry::class);
        $registry->has('Interface')->willReturn(true);
        $registry->has('NonInterface')->willReturn(false);

        $resolver = new Resolver($registry->reveal());
        self::assertTrue($resolver->has('Interface'));
        self::assertFalse($resolver->has('NonInterface'));
    }
}
