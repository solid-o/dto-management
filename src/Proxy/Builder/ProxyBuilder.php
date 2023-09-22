<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Exception\InvalidProxiedClassException;
use ProxyManager\ProxyGenerator\Assertion\CanProxyAssertion;
use ProxyManager\ProxyGenerator\Util\Properties;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Solido\DtoManagement\Exception\FinalMethodException;
use Solido\DtoManagement\Exception\MethodAlreadyDeclaredException;
use Solido\DtoManagement\Exception\NonExistentInterfaceException;
use Solido\DtoManagement\Exception\NonExistentMethodException;
use Solido\DtoManagement\Exception\NonExistentPropertyException;
use Solido\DtoManagement\Exception\PropertyAlreadyDeclaredException;
use Solido\DtoManagement\Exception\TraitAlreadyAddedException;
use Solido\DtoManagement\Proxy\ProxyInterface;

use function array_column;
use function array_filter;
use function count;
use function implode;
use function in_array;
use function interface_exists;
use function sprintf;

class ProxyBuilder
{
    private const MAGIC_METHODS = [
        '__get',
        '__set',
        '__isset',
        '__construct',
    ];

    public ReflectionClass $class;
    public Properties $properties;

    /**
     * @var array<string, array<mixed>>
     * @phpstan-var array<class-string, array{aliases: array{method: non-empty-string, alias: non-empty-string, visibility?: ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED}[], overrides: array{method: string, traitToReplace: string}[]}>
     */
    private array $traits;

    /**
     * @var array<string, array<mixed>>
     * @phpstan-var array<string, array{generator: PropertyGenerator, constructor: string}>
     */
    private array $extraProperties;

    /**
     * @var array<string, array<mixed>>
     * @phpstan-var array<string, array{generator: MethodGenerator}>
     */
    private array $extraMethods;

    /**
     * @var string[]
     * @phpstan-var class-string[]
     */
    private array $interfaces;

    /** @var array<string, ReflectionProperty> */
    private array $accessibleProperties;

    /** @var array<string, ReflectionMethod> */
    private array $accessibleMethods;

    /** @var array<string, array<Interceptor>> */
    private array $methodInterceptors = [];

    /** @var array<string, array<Wrapper>> */
    private array $methodWrappers = [];

    /** @var array<string, array<Interceptor>> */
    private array $propertyInterceptors = [];

    public function __construct(ReflectionClass $class)
    {
        CanProxyAssertion::assertClassCanBeProxied($class, false);
        if (PHP_VERSION_ID >= 80200 && $class->isReadOnly()) {
            throw new InvalidProxiedClassException(sprintf('Provided class "%s" is readonly and cannot be proxied', $class->getName()));
        }

        $this->class = $class;
        $this->properties = Properties::fromReflectionClass($class);
        $this->interfaces = [ProxyInterface::class];
        $this->traits = [];

        $this->extraProperties = [];
        $this->extraMethods = [];
        $this->accessibleProperties = [];
        $this->accessibleMethods = [];

        foreach ($this->properties->getAccessibleProperties() as $property) {
            $propertyName = $property->getName();
            $this->accessibleProperties[$propertyName] = $property;
        }

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $this->accessibleMethods[$method->getName()] = $method;
        }
    }

    /**
     * Whether this build has no interceptors and additional properties/methods.
     */
    public function empty(): bool
    {
        return count($this->propertyInterceptors) === 0 &&
            count($this->methodInterceptors) === 0 &&
            count($this->methodWrappers) === 0 &&
            count($this->extraMethods) === 0 &&
            count($this->extraProperties) === 0;
    }

    /**
     * Gets the list of interfaces to be implemented by the generated proxy.
     *
     * @return string[]
     * @phpstan-return class-string[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * Get extra constructor code.
     */
    public function getConstructorCode(): string
    {
        return implode(";\n", array_filter(array_column($this->extraProperties, 'constructor'))) . ";\n";
    }

    /**
     * Adds an interface to be implemented by the generated proxy.
     *
     * @phpstan-param class-string $interface
     */
    public function addInterface(string $interface): void
    {
        if (! interface_exists($interface)) {
            throw new NonExistentInterfaceException(sprintf('Interface "%s" does not exist.', $interface));
        }

        if (in_array($interface, $this->interfaces, true)) {
            return;
        }

        $this->interfaces[] = $interface;
    }

    /**
     * Adds a trait to be added by this proxy.
     *
     * @param array<string, mixed> $aliases
     * @param array<string, string> $overrides
     * @phpstan-param class-string $traitName
     * @phpstan-param array{method: non-empty-string, alias: non-empty-string, visibility?: ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED}[] $aliases
     * @phpstan-param array{method: non-empty-string, traitToReplace: non-empty-string}[] $overrides
     */
    public function addTrait(string $traitName, array $aliases = [], array $overrides = []): void
    {
        if (isset($this->traits[$traitName])) {
            throw new TraitAlreadyAddedException(sprintf('Trait "%s" has been already added for proxy of class %s', $traitName, $this->class->getName()));
        }

        $this->traits[$traitName] = [
            'aliases' => $aliases,
            'overrides' => $overrides,
        ];
    }

    /**
     * Gets the traits to be added to the proxy.
     *
     * @return array<string, mixed>
     * @phpstan-return array<class-string, array{aliases: array{method: non-empty-string, alias: non-empty-string, visibility?: ReflectionMethod::IS_PUBLIC|ReflectionMethod::IS_PRIVATE|ReflectionMethod::IS_PROTECTED}[], overrides: array{method: string, traitToReplace: string}[]}>
     */
    public function getTraits(): array
    {
        return $this->traits;
    }

    /**
     * Add interceptor of property.
     */
    public function addPropertyInterceptor(string $propertyName, Interceptor $interceptor): void
    {
        if (! isset($this->accessibleProperties[$propertyName])) {
            throw new NonExistentPropertyException(sprintf('Property "%s" is non-existent or not accessible on class %s', $propertyName, $this->class->getName()));
        }

        $this->propertyInterceptors[$propertyName][] = $interceptor;
    }

    /**
     * Whether a property has been declared (and accessible) from the proxy context.
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->extraProperties[$name]) || isset($this->accessibleProperties[$name]);
    }

    /**
     * Adds an extra property to the proxy.
     * Name cannot conflict with public and protected properties of the base class.
     */
    public function addProperty(PropertyGenerator $generator, string $constructor = ''): void
    {
        $name = $generator->getName();
        if ($this->hasProperty($name)) {
            throw new PropertyAlreadyDeclaredException(sprintf('Property "%s" has been already declared.', $name));
        }

        Util::assertValidPhpCode($constructor . ';');

        $this->extraProperties[$name] = [
            'generator' => $generator,
            'constructor' => $constructor,
        ];
    }

    /**
     * Gets extra properties generators.
     *
     * @return PropertyGenerator[]
     */
    public function getExtraProperties(): array
    {
        return array_column($this->extraProperties, 'generator');
    }

    /**
     * Gets the interceptors for given property.
     *
     * @return Interceptor[]
     */
    public function getPropertyInterceptors(string $propertyName): array
    {
        return $this->propertyInterceptors[$propertyName] ?? [];
    }

    /**
     * Whether a method has been declared (and accessible) from the proxy context.
     */
    public function hasMethod(string $name): bool
    {
        return isset($this->extraMethods[$name]) || isset($this->accessibleMethods[$name]);
    }

    /**
     * Add interceptor of method.
     */
    public function addMethodInterceptor(string $methodName, Interceptor $interceptor): void
    {
        if (! isset($this->accessibleMethods[$methodName])) {
            throw new NonExistentMethodException(sprintf('Method "%s" is non-existent or not accessible on class %s', $methodName, $this->class->getName()));
        }

        if ($this->accessibleMethods[$methodName]->isFinal()) {
            throw new FinalMethodException(sprintf('Method "%s" is final on class %s and cannot be intercepted', $methodName, $this->class->getName()));
        }

        $this->methodInterceptors[$methodName][] = $interceptor;
    }

    /**
     * Add wrapper of method.
     */
    public function addMethodWrapper(string $methodName, Wrapper $wrapper): void
    {
        if (! isset($this->accessibleMethods[$methodName])) {
            throw new NonExistentMethodException(sprintf('Method "%s" is non-existent or not accessible on class %s', $methodName, $this->class->getName()));
        }

        if ($this->accessibleMethods[$methodName]->isFinal()) {
            throw new FinalMethodException(sprintf('Method "%s" is final on class %s and cannot be wrapped', $methodName, $this->class->getName()));
        }

        $this->methodWrappers[$methodName][] = $wrapper;
    }

    /**
     * Adds an extra method to the proxy.
     * Name cannot conflict with public and protected methods of the base class.
     */
    public function addMethod(MethodGenerator $generator): void
    {
        $name = $generator->getName();
        if (isset($this->extraMethods[$name]) || in_array($name, self::MAGIC_METHODS, true)) {
            throw new MethodAlreadyDeclaredException(sprintf('Method "%s" has been already declared.', $name));
        }

        $this->extraMethods[$name] = ['generator' => $generator];
    }

    /**
     * Gets the interceptors for given method.
     *
     * @return Interceptor[]
     */
    public function getMethodInterceptors(string $methodName): array
    {
        return $this->methodInterceptors[$methodName] ?? [];
    }

    /**
     * Gets the interceptors for given method.
     *
     * @return Wrapper[]
     */
    public function getMethodWrappers(string $methodName): array
    {
        return $this->methodWrappers[$methodName] ?? [];
    }

    /**
     * Gets extra properties generators.
     *
     * @return MethodGenerator[]
     */
    public function getExtraMethods(): array
    {
        return array_column($this->extraMethods, 'generator');
    }
}
