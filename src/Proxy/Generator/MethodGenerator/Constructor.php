<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator\MethodGenerator;

use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use Laminas\Code\Reflection\MethodReflection;
use ProxyManager\Generator\MethodGenerator;
use ProxyManager\ProxyGenerator\Util\Properties;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;

use function array_filter;
use function array_key_exists;
use function array_map;
use function assert;
use function count;
use function implode;
use function reset;
use function var_export;

class Constructor extends MethodGenerator
{
    /**
     * Emits the code for constructor method.
     */
    public static function generateMethod(ReflectionClass $originalClass, PropertyGenerator $valueHolder, ProxyBuilder $proxyBuilder): self
    {
        $originalConstructor = self::getConstructor($originalClass);

        $constructor = $originalConstructor ? self::fromReflection($originalConstructor) : new self('__construct');
        assert($constructor instanceof self);

        $excludedProperties = [];
        foreach ($proxyBuilder->properties->getAccessibleProperties() as $property) {
            if (count($proxyBuilder->getPropertyInterceptors($property->getName())) !== 0) {
                continue;
            }

            $excludedProperties[] = $property->getName();
        }

        $properties = $proxyBuilder->properties->filter($excludedProperties);

        $constructor->setDocBlock('{@inheritDoc}');
        $constructor->setBody(
            '$this->' . $valueHolder->getName() . ' = ' . self::generateAnonymousClassValueHolder($properties, $originalClass->getDefaultProperties()) . "\n"
            . self::generateUnsetAccessiblePropertiesCode($properties)
            . self::generateOriginalConstructorCall($originalClass)
            . $proxyBuilder->getConstructorCode(),
        );

        return $constructor;
    }

    /**
     * Generates a parent constructor call.
     */
    private static function generateOriginalConstructorCall(ReflectionClass $class): string
    {
        $originalConstructor = self::getConstructor($class);
        if ($originalConstructor === null) {
            return '';
        }

        $constructor = self::fromReflection($originalConstructor);
        $nameGenerator = static fn (ParameterGenerator $parameter) => ($parameter->getVariadic() ? '...' : '') . '$' . $parameter->getName();

        return 'parent::' . $constructor->getName() . '('
            . implode(', ', array_map($nameGenerator, $constructor->getParameters()))
            . ");\n";
    }

    /**
     * Retrieves the constructor.
     */
    private static function getConstructor(ReflectionClass $class): MethodReflection|null
    {
        $constructors = array_map(
            static fn (ReflectionMethod $method) => new MethodReflection($method->getDeclaringClass()->getName(), $method->getName()),
            array_filter($class->getMethods(), static fn (ReflectionMethod $method) => $method->isConstructor()),
        );

        return reset($constructors) ?: null;
    }

    /** @param array<string, mixed> $defaults */
    private static function generateAnonymousClassValueHolder(Properties $properties, array $defaults): string
    {
        $accessibleProperties = $properties->getAccessibleProperties();

        return "new class extends \stdClass {\n" .
            implode("\n", array_map(static function (ReflectionProperty $property) use (&$defaults): string {
                $name = $property->getName();

                return '    public $' . $name . (array_key_exists($name, $defaults) ? ' = ' . var_export($defaults[$name], true) : '') . ';';
            }, $accessibleProperties))
            . "\n};";
    }

    private static function generateUnsetAccessiblePropertiesCode(Properties $properties): string
    {
        $accessibleProperties = $properties->getAccessibleProperties();
        if (! $accessibleProperties) {
            return '';
        }

        return self::generateUnsetStatement($accessibleProperties) . "\n\n";
    }

    /** @param array<string, ReflectionProperty> $properties */
    private static function generateUnsetStatement(array $properties): string
    {
        $generator = static fn (ReflectionProperty $property) => '$this->' . $property->getName();

        return 'unset(' . implode(', ', array_map($generator, $properties)) . ');';
    }
}
