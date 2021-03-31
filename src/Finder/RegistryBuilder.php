<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder;

use Kcs\ClassFinder\Finder\ComposerFinder;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Solido\DtoManagement\Finder\ArgumentResolver\ArgumentResolver;
use Solido\DtoManagement\Finder\ArgumentResolver\ArgumentValueResolverInterface;
use Solido\DtoManagement\Finder\ArgumentResolver\DefaultValueArgumentValueResolver;
use Solido\DtoManagement\Finder\ArgumentResolver\NullArgumentValueResolver;
use Solido\DtoManagement\Finder\ArgumentResolver\ServiceContainerArgumentValueResolver;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;

use function array_map;
use function array_unshift;
use function assert;
use function is_string;
use function Safe\preg_match;
use function str_replace;

class RegistryBuilder
{
    private ?AccessInterceptorFactory $proxyFactory = null;
    private string $namespace;

    /**
     * @var array<string, bool>
     * @phpstan-var array<class-string, bool>
     */
    private array $excludedInterface = [];

    /** @var ArgumentValueResolverInterface[] */
    private array $argumentValueResolvers;

    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
        $this->argumentValueResolvers = [];
    }

    /**
     * @phpstan-param class-string $interface
     */
    public function excludeInterface(string $interface): self
    {
        $this->excludedInterface[$interface] = true;

        return $this;
    }

    public function withProxyFactory(AccessInterceptorFactory $proxyFactory): self
    {
        $this->proxyFactory = $proxyFactory;

        return $this;
    }

    public function withArgumentValueResolver(ArgumentValueResolverInterface $valueResolver): self
    {
        array_unshift($this->argumentValueResolvers, $valueResolver);

        return $this;
    }

    public function withServiceContainer(ContainerInterface $container): self
    {
        return $this->withArgumentValueResolver(new ServiceContainerArgumentValueResolver($container));
    }

    public function build(): ServiceLocatorRegistryInterface
    {
        $proxyFactory = $this->proxyFactory ?? new AccessInterceptorFactory();
        $argumentResolver = new ArgumentResolver([...$this->argumentValueResolvers, new DefaultValueArgumentValueResolver(), new NullArgumentValueResolver()]);

        $finder = new ComposerFinder();
        $finder->inNamespace($this->namespace);

        [$interfaces, $modelsByInterface] = $this->collectInterfaces($finder);

        /** @phpstan-var array<class-string, callable(): ServiceLocator> $locators */
        $locators = [];
        foreach ($modelsByInterface as $interface => $versions) {
            if (! isset($interfaces[$interface])) {
                continue;
            }

            /** @phpstan-var array<string, callable(): mixed> $factories */
            $factories = array_map(static fn (string $className) => static function () use ($className, $proxyFactory, $argumentResolver) {
                /** @phpstan-var class-string $className */
                $proxyClass = $proxyFactory->generateProxy($className);

                $constructor = (new ReflectionClass($className))->getConstructor();
                $constructorArguments = $constructor !== null ? $argumentResolver->getArguments($constructor) : [];

                return new $proxyClass(...$constructorArguments);
            }, $versions);
            $locators[$interface] = static fn () => new ServiceLocator($factories);
        }

        return new ServiceLocatorRegistry($locators);
    }

    /**
     * @return array<string, ReflectionClass>|array<string, array<string, string>>[]
     *
     * @phpstan-return array{0: array<class-string, ReflectionClass>, 1: array<class-string, array<string, string>>}
     */
    private function collectInterfaces(ComposerFinder $finder): array
    {
        $versionPattern = '/^' . str_replace('\\', '\\\\', $this->namespace) . '\\\\v(.+?)\\\\v(.+?)\\\\/';

        $interfaces = [];
        $modelsByInterface = [];

        /**
         * @phpstan-var class-string $class
         */
        foreach ($finder as $class => $reflector) {
            assert(is_string($class));
            assert($reflector instanceof ReflectionClass);

            if ($reflector->isInterface()) {
                if (! isset($this->excludedInterface[$reflector->getName()])) {
                    $interfaces[$class] = $reflector;
                }

                continue;
            }

            if (! preg_match($versionPattern, $class, $m)) {
                continue;
            }

            $version = str_replace('_', '.', $m[2]);
            assert(is_string($version));

            foreach ($reflector->getInterfaces() as $interface) {
                $modelsByInterface[$interface->getName()][$version] = $reflector->getName();
            }
        }

        return [$interfaces, $modelsByInterface];
    }
}
