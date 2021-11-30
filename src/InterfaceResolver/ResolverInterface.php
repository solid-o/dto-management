<?php

declare(strict_types=1);

namespace Solido\DtoManagement\InterfaceResolver;

/**
 * Resolves model interfaces to service instances.
 */
interface ResolverInterface
{
    /**
     * Resolve the given interface and return the corresponding
     * service from the service container.
     *
     * @param class-string<T> $interface
     * @param mixed $version
     *
     * @return T
     *
     * @template T
     */
    public function resolve(string $interface, $version = 'latest');

    /**
     * Checks whether the given interface could be resolved.
     *
     * @phpstan-param class-string $interface
     */
    public function has(string $interface): bool;
}
