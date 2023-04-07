<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

use Psr\Container\ContainerInterface;
use Solido\DtoManagement\Exception\RuntimeException;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;

use function array_keys;

class ServiceLocatorRegistry implements ServiceLocatorRegistryInterface
{
    /** @phpstan-var array<class-string, callable(): ServiceLocator> */
    private array $locators;

    /** @phpstan-param array<class-string, callable(): ServiceLocator> $locators */
    public function __construct(array $locators)
    {
        $this->locators = $locators;
    }

    /**
     * Creates a locator registry from namespace.
     *
     * @param string[] $excludedInterfaces
     * @phpstan-param class-string[] $excludedInterfaces
     */
    public static function createFromNamespace(
        string $namespace,
        array $excludedInterfaces = [],
        ?AccessInterceptorFactory $proxyFactory = null,
        ?ContainerInterface $container = null
    ): ServiceLocatorRegistryInterface {
        $builder = new RegistryBuilder($namespace);

        foreach ($excludedInterfaces as $interface) {
            $builder->excludeInterface($interface);
        }

        if ($proxyFactory !== null) {
            $builder->withProxyFactory($proxyFactory);
        }

        if ($container !== null) {
            $builder->withServiceContainer($container);
        }

        return $builder->build();
    }

    /** @phpstan-param class-string $interface */
    public function get(string $interface): ServiceLocator
    {
        if (! isset($this->locators[$interface])) {
            throw new RuntimeException('Cannot find service locator for "' . $interface . '"');
        }

        return $this->locators[$interface]();
    }

    /** @phpstan-param class-string $interface */
    public function has(string $interface): bool
    {
        return isset($this->locators[$interface]);
    }

    /**
     * Gets all the interfaces names registered in this registry.
     *
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getInterfaces(): array
    {
        return array_keys($this->locators);
    }
}
