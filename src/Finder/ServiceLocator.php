<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Solido\DtoManagement\Exception\ServiceCircularReferenceException;
use Solido\DtoManagement\Exception\ServiceNotFoundException;

use function array_key_last;
use function array_keys;
use function assert;
use function end;
use function is_string;
use function str_replace;
use function uksort;
use function version_compare;

/**
 * @internal
 *
 * @template T of object
 */
class ServiceLocator implements ContainerInterface
{
    /** @var array<string, string> */
    private array $loading;
    private string $cacheItemPrefix;

    /** @param array<string, callable> $factories */
    public function __construct(private string $interfaceName, private array $factories, private CacheItemPoolInterface|null $cache = null)
    {
        $this->loading = [];
        $this->cacheItemPrefix = str_replace('\\', '', $this->interfaceName) . '_';
        uksort($this->factories, static fn (string $a, string $b): int => version_compare($a, $b));
    }

    public function has(mixed $id): bool
    {
        $key = array_keys($this->factories)[0];

        return version_compare((string) $id, (string) $key, '>=');
    }

    /**
     * {@inheritDoc}
     *
     * @return T
     */
    public function get(mixed $id): object
    {
        $last = null;
        if ($id === 'latest') {
            $id = array_key_last($this->factories);
        }

        $id = (string) $id;
        $cacheItem = $this->cache?->getItem($this->cacheItemPrefix . $id);
        if ($cacheItem !== null && $cacheItem->isHit()) {
            $last = $cacheItem->get();
            assert(is_string($last));
        } else {
            foreach ($this->factories as $version => $service) {
                $version = (string) $version;
                if (! version_compare($version, $id, '<=')) {
                    break;
                }

                $last = $version;
            }

            if ($last === null) {
                throw new ServiceNotFoundException($this->interfaceName, $id, $this->loading ? end($this->loading) : null, null, array_keys($this->factories));
            }

            if ($cacheItem !== null) {
                $cacheItem->set($last);
                assert($this->cache !== null);
                $this->cache->saveDeferred($cacheItem);
            }
        }

        $factory = $this->factories[$last];
        if ($factory === true) { // @phpstan-ignore-line
            throw new ServiceCircularReferenceException($last, [$last, $last]);
        }

        $this->factories[$last] = true; // @phpstan-ignore-line
        $this->loading[$id] = $id;
        try {
            return $factory();
        } finally {
            $this->factories[$last] = $factory;
            unset($this->loading[$id]);
        }
    }

    public function __invoke(string $id): object|null
    {
        try {
            return $this->get($id);
        } catch (ServiceNotFoundException) {
            return null;
        }
    }

    /**
     * Gets all the registered versions for the current interface.
     *
     * @return string[]
     */
    public function getVersions(): array
    {
        return array_keys($this->factories);
    }
}
