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

/** @internal */
class ServiceLocator implements ContainerInterface
{
    /** @var array<string, string> */
    private array $loading;
    /** @var array<string, string> */
    private array $resolvedVersions = [];
    private string $cacheItemPrefix;
    private string $firstVersion;
    private string $latestVersion;

    /** @param array<string, callable> $factories */
    public function __construct(private string $interfaceName, private array $factories, private CacheItemPoolInterface|null $cache = null)
    {
        $this->loading = [];
        $this->cacheItemPrefix = str_replace('\\', '', $this->interfaceName) . '_';
        uksort($this->factories, static fn (string $a, string $b): int => version_compare($a, $b));
        $versions = array_keys($this->factories);
        $this->firstVersion = (string) $versions[0];
        $this->latestVersion = (string) array_key_last($this->factories);
    }

    public function has(mixed $id): bool
    {
        return version_compare((string) $id, $this->firstVersion, '>=');
    }

    public function get(mixed $id): object
    {
        $last = null;
        if ($id === 'latest') {
            $id = $this->latestVersion;
        }

        $id = (string) $id;
        if (isset($this->resolvedVersions[$id])) {
            $last = $this->resolvedVersions[$id];
        } else {
            $last = $this->resolveVersion($id);
            $this->resolvedVersions[$id] = $last;
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

    private function resolveVersion(string $id): string
    {
        $last = null;
        $cacheItem = $this->cache?->getItem($this->cacheItemPrefix . $id);
        if ($cacheItem !== null && $cacheItem->isHit()) {
            $last = $cacheItem->get();
            assert(is_string($last));

            return $last;
        }

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
            $this->cache->saveDeferred($cacheItem);
        }

        return $last;
    }
}
