<?php

declare(strict_types=1);

namespace Solido\DtoManagement\InterfaceResolver;

use Psr\Http\Message\ServerRequestInterface;
use Solido\DtoManagement\Exception\InvalidArgumentException;
use Solido\DtoManagement\Finder\ServiceLocatorRegistryInterface;
use Stringable;
use Symfony\Component\HttpFoundation\Request;

use function get_debug_type;
use function is_string;
use function Safe\sprintf;

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
        } elseif ($version instanceof ServerRequestInterface) {
            $version = $version->getAttribute('_version', 'latest');
        }

        if ($version !== null && ! is_string($version)) {
            if (! $version instanceof Stringable) {
                throw new InvalidArgumentException(sprintf('Version must be a string or a stringable object, %s passed', get_debug_type($version)));
            }

            $version = (string) $version;
        }

        return $this->registry->get($interface)->get($version ?? 'latest');
    }

    public function has(string $interface): bool
    {
        return $this->registry->has($interface);
    }
}
