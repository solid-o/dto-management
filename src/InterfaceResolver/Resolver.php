<?php

declare(strict_types=1);

namespace Solido\DtoManagement\InterfaceResolver;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;

class Resolver implements ResolverInterface
{
    private ServiceLocatorRegistryInterface $registry;

    public function __construct(ServiceLocatorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $interface, $version = 'latest')
    {
        $locator = $this->registry->get($interface);

        return $locator->get($version);
    }

    public function has(string $interface): bool
    {
        return $this->registry->has($interface);
    }
}
