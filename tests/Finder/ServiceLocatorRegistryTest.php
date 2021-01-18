<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Finder;

use Laminas\Code\Generator\MethodGenerator;
use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Exception\RuntimeException;
use Solido\DtoManagement\Finder\ServiceLocator;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use Solido\DtoManagement\Proxy\Factory\Configuration;
use Solido\DtoManagement\Proxy\ProxyInterface;
use Solido\DtoManagement\Tests\Fixtures;

class ServiceLocatorRegistryTest extends TestCase
{
    public function testLoadShouldCreateModelServices(): void
    {
        $registry = ServiceLocatorRegistry::createFromNamespace('Solido\\DtoManagement\\Tests\\Fixtures\\Model');

        self::assertInstanceOf(ServiceLocatorRegistry::class, $registry);
        self::assertTrue($registry->has(Fixtures\Model\Interfaces\UserInterface::class));

        $locator = $registry->get(Fixtures\Model\Interfaces\UserInterface::class);

        self::assertInstanceOf(ServiceLocator::class, $locator);
        self::assertEquals(['20171215'], $locator->getVersions());
    }

    public function testLoadShouldCreateProxies(): void
    {
        $configuration = new Configuration();
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethod(new MethodGenerator('someMethod'));
            }
        });

        $proxyFactory = new AccessInterceptorFactory($configuration);
        $registry = ServiceLocatorRegistry::createFromNamespace(
            'Solido\\DtoManagement\\Tests\\Fixtures\\Model',
            [],
            $proxyFactory
        );

        $locator = $registry->get(Fixtures\Model\Interfaces\UserInterface::class);

        $proxy = $locator->get('20171215');
        self::assertInstanceOf(ProxyInterface::class, $proxy);
        self::assertTrue(method_exists($proxy, 'someMethod'));
    }

    public function testShouldThrowIfNonexistentInterfaceIsRequested(): void
    {
        $this->expectException(RuntimeException::class);

        $registry = ServiceLocatorRegistry::createFromNamespace('Solido\\DtoManagement\\Tests\\Fixtures\\Model');
        $registry->get('NonExistentInterface');
    }

    public function testLoadShouldCreateModelServicesForSemVer(): void
    {
        $registry = ServiceLocatorRegistry::createFromNamespace('Solido\\DtoManagement\\Tests\\Fixtures\\SemVerModel');

        self::assertInstanceOf(ServiceLocatorRegistry::class, $registry);
        self::assertTrue($registry->has(Fixtures\SemVerModel\Interfaces\UserInterface::class));

        $locator = $registry->get(Fixtures\SemVerModel\Interfaces\UserInterface::class);

        self::assertInstanceOf(ServiceLocator::class, $locator);
        self::assertEquals(['1.0', '1.1', '1.2', '2.0.alpha.1'], $locator->getVersions());

        $interfaces = $registry->getInterfaces();
        sort($interfaces);

        self::assertEquals([
            Fixtures\SemVerModel\Interfaces\FooInterface::class,
            Fixtures\SemVerModel\Interfaces\UserInterface::class,
        ], $interfaces);
    }
}
