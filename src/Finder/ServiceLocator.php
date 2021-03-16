<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

use Psr\Container\ContainerInterface;
use Solido\DtoManagement\Exception\ServiceCircularReferenceException;
use Solido\DtoManagement\Exception\ServiceNotFoundException;

use function array_key_last;
use function array_keys;
use function Safe\uksort;
use function version_compare;

class ServiceLocator implements ContainerInterface
{
    /** @var array<string, callable|true> */
    private array $factories;

    /**
     * @param array<string, callable> $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
        uksort($this->factories, 'version_compare');
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        $key = array_keys($this->factories)[0];

        return version_compare((string) $id, (string) $key, '>=');
    }

    /**
     * {@inheritdoc}
     */
    public function get($id): object
    {
        if ($id === 'latest') {
            $id = (string) array_key_last($this->factories);
        }

        $id = (string) $id;
        $last = null;

        foreach ($this->factories as $version => $service) {
            if (! version_compare((string) $version, $id, '<=')) {
                break;
            }

            $last = $version;
        }

        if ($last === null) {
            throw new ServiceNotFoundException($id, null, null, array_keys($this->factories));
        }

        $factory = $this->factories[$last];
        if ($factory === true) {
            throw new ServiceCircularReferenceException($last, [$last, $last]);
        }

        $this->factories[$last] = true;
        try {
            return $factory();
        } finally {
            $this->factories[$last] = $factory;
        }
    }

    public function __invoke(string $id): ?object
    {
        try {
            return $this->get($id);
        } catch (ServiceNotFoundException $e) {
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
