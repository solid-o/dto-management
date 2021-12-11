<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\InterfaceResolver;

use Nyholm\Psr7\ServerRequest;
use Prophecy\PhpUnit\ProphecyTrait;
use Solido\DtoManagement\Exception\InvalidArgumentException;
use Solido\DtoManagement\Finder\ServiceLocator;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\InterfaceResolver\Resolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ResolverTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @dataProvider resolverVersionProvider
     */
    public function testGetShouldCallCorrectLocator($expected, $version): void
    {
        $registry = $this->prophesize(ServiceLocatorRegistry::class);
        $registry->get('Interface')->willReturn($locator = $this->prophesize(ServiceLocator::class));
        $locator->get($expected)->willReturn($obj = new \stdClass());

        $resolver = new Resolver($registry->reveal());
        self::assertSame($obj, $resolver->resolve('Interface', $version));
    }

    public function resolverVersionProvider(): iterable
    {
        yield ['latest', null];
        yield ['latest', 'latest'];
        yield ['2.0', '2.0'];

        $request = new Request();
        $request->attributes->set('_version', '2.0');

        yield ['2.0', $request];

        $request = new ServerRequest('GET', '/');
        $request = $request->withAttribute('_version', '2.1');

        yield ['2.1', $request];
        yield ['2.3', new class implements \Stringable {
            public function __toString()
            {
                return '2.3';
            }
        }];
    }

    public function testGetShouldThrowIfVersionIsNotStringable(): void
    {
        $registry = $this->prophesize(ServiceLocatorRegistry::class);
        $resolver = new Resolver($registry->reveal());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Version must be a string or a stringable object, array passed');
        $resolver->resolve('Interface', ['not-stringable']);
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
