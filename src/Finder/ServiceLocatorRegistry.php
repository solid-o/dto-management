<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

use Kcs\ClassFinder\Finder\ComposerFinder;
use ReflectionClass;
use Solido\DtoManagement\Exception\RuntimeException;

use function array_keys;
use function array_map;
use function assert;
use function in_array;
use function is_string;
use function Safe\preg_match;
use function str_replace;

class ServiceLocatorRegistry implements ServiceLocatorRegistryInterface
{
    /** @phpstan-var array<class-string, callable(): ServiceLocator> */
    private array $locators;

    /**
     * @phpstan-param array<class-string, callable(): ServiceLocator> $locators
     */
    public function __construct(array $locators)
    {
        $this->locators = $locators;
    }

    /**
     * Creates a locator registry from namespace.
     *
     * @param string[] $excludedInterfaces
     *
     * @phpstan-param class-string[] $excludedInterfaces
     */
    public static function createFromNamespace(string $namespace, array $excludedInterfaces = []): ServiceLocatorRegistryInterface
    {
        $finder = new ComposerFinder();
        $finder->inNamespace($namespace);

        $interfaces = [];
        $modelsByInterface = [];

        foreach ($finder as $class => $reflector) {
            assert($reflector instanceof ReflectionClass);

            if ($reflector->isInterface()) {
                if (! in_array($reflector->getName(), $excludedInterfaces, true)) {
                    $interfaces[$class] = $reflector;
                }

                continue;
            }

            if (! preg_match('/^' . str_replace('\\', '\\\\', $namespace) . '\\\\v(.+?)\\\\v(.+?)\\\\/', $class, $m)) {
                continue;
            }

            $version = str_replace('_', '.', $m[2]);
            assert(is_string($version));

            foreach ($reflector->getInterfaces() as $interface) {
                $modelsByInterface[$interface->getName()][$version] = $reflector->getName();
            }
        }

        /** @phpstan-var array<class-string, callable(): ServiceLocator> $locators */
        $locators = [];
        foreach ($modelsByInterface as $interface => $versions) {
            if (! isset($interfaces[$interface])) {
                continue;
            }

            /** @phpstan-var array<string, callable(): mixed> $factories */
            $factories = array_map(static fn (string $className) => static fn () => new $className(), $versions);
            $locators[$interface] = static fn () => new ServiceLocator($factories);
        }

        return new self($locators);
    }

    /**
     * @phpstan-param class-string $interface
     */
    public function get(string $interface): ServiceLocator
    {
        if (! isset($this->locators[$interface])) {
            throw new RuntimeException('Cannot find service locator for "' . $interface . '"');
        }

        return $this->locators[$interface]();
    }

    /**
     * @phpstan-param class-string $interface
     */
    public function has(string $interface): bool
    {
        return isset($this->locators[$interface]);
    }

    /**
     * Gets all the interfaces names registered in this registry.
     *
     * @return string[]
     *
     * @phpstan-return class-string[]
     */
    public function getInterfaces(): array
    {
        return array_keys($this->locators);
    }
}
