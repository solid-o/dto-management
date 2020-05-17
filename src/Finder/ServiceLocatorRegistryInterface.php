<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

interface ServiceLocatorRegistryInterface
{
    /**
     * Gets a service locator for the given interface.
     *
     * @phpstan-param class-string $interface
     */
    public function get(string $interface): ServiceLocator;

    /**
     * Whether if an interface is present on this registry.
     *
     * @phpstan-param class-string $interface
     */
    public function has(string $interface): bool;
}
