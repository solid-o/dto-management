<?php

declare(strict_types=1);

namespace Solido\DtoManagement\InterfaceResolver;

use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Symfony\Component\HttpFoundation\Request;

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
    public function resolve(string $interface, $version = null)
    {
        if ($version instanceof Request) {
            $version = $version->attributes->get('_version', 'latest');
        }

        return $this->registry->get($interface)->get($version ?? 'latest');
    }

    public function has(string $interface): bool
    {
        return $this->registry->has($interface);
    }
}
