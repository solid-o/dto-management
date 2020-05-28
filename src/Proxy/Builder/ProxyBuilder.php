<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

use Laminas\Code\Generator\MethodGenerator;
use Laminas\Code\Generator\PropertyGenerator;
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
use Solido\DtoManagement\Proxy\ProxyInterface;
use function array_column;
use function array_filter;
use function count;
use function implode;
use function in_array;
use function interface_exists;
use function Safe\sprintf;
use function str_replace;

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

    /** @var array<string, array<Interceptor>> */
    private array $propertyInterceptors = [];

    public function __construct(ReflectionClass $class)
    {
        CanProxyAssertion::assertClassCanBeProxied($class, false);

        $this->class = $class;
        $this->properties = Properties::fromReflectionClass($class);
        $this->interfaces = [ProxyInterface::class];

        $this->extraProperties = [];
        $this->extraMethods = [];
        $this->accessibleProperties = [];
        $this->accessibleMethods = [];

        foreach ($this->properties->getAccessibleProperties() as $property) {
            $propertyName = str_replace("\0*\0", '', $property->getName());
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
            count($this->extraMethods) === 0 &&
            count($this->extraProperties) === 0;
    }

    /**
     * Gets the list of interfaces to be implemented by the generated proxy.
     *
     * @return string[]
     *
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
    public function addProperty(PropertyGenerator $generator, string $constructor): void
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
     * Gets extra properties generators.
     *
     * @return MethodGenerator[]
     */
    public function getExtraMethods(): array
    {
        return array_column($this->extraMethods, 'generator');
    }
}
