<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator\Util;

use InvalidArgumentException;
use Laminas\Code\Generator\PropertyGenerator;

use function array_map;
use function implode;
use function Safe\sprintf;

/**
 * Generates code necessary to simulate a fatal error in case of unauthorized
 * access to class members in magic methods even when in child classes and dealing
 * with protected members.
 */
class PublicScopeSimulator
{
    public const OPERATION_GET   = 'get';
    public const OPERATION_ISSET = 'isset';

    /**
     * Generates code for simulating access to a property from the scope that is accessing a proxy.
     * This is done by introspecting `debug_backtrace()` and then binding a closure to the scope
     * of the parent caller.
     *
     * @phpstan-param self::OPERATION_* $operationType
     *
     * @throws InvalidArgumentException
     */
    public static function getPublicAccessSimulationCode(
        string $operationType,
        string $nameParameter,
        PropertyGenerator $valueHolder,
        string $returnPropertyName
    ): string {
        $byRef  = self::getByRefReturnValue($operationType);
        $target = '$this->' . $valueHolder->getName();

        $originalClassReflection = 'new \\ReflectionClass(get_parent_class($this))';
        $accessorEvaluation = '$' . $returnPropertyName . ' = ' . $byRef . '$accessor();';

        return '$realInstanceReflection = ' . $originalClassReflection . ';' . "\n\n"
            . 'if (! $realInstanceReflection->hasProperty($' . $nameParameter . ')) {' . "\n"
            . '    $targetObject = ' . $target . ';' . "\n\n"
            . self::getUndefinedPropertyNotice($operationType, $nameParameter)
            . '    ' . self::getOperation($operationType, $nameParameter) . "\n"
            . '}' . "\n\n"
            . '$targetObject = ' . self::getTargetObject($valueHolder) . ";\n"
            . '$accessor = function ' . $byRef . '() use ('
            . implode(', ', array_map(
                static fn (string $parameterName): string => '$' . $parameterName,
                ['targetObject', $nameParameter]
            ))
            . ') {' . "\n"
            . '    ' . self::getOperation($operationType, $nameParameter) . "\n"
            . "};\n"
            . self::generateScopeReBind()
            . $accessorEvaluation;
    }

    /**
     * This will generate code that triggers a notice if access is attempted on a non-existing property
     *
     * @phpstan-param self::OPERATION_* $operationType
     */
    private static function getUndefinedPropertyNotice(string $operationType, string $nameParameter): string
    {
        if ($operationType !== self::OPERATION_GET) {
            return '';
        }

        return '    $backtrace = debug_backtrace(false, 1);' . "\n"
            . '    trigger_error(' . "\n"
            . '        sprintf(' . "\n"
            . '            \'Undefined property: %s::$%s in %s on line %s\',' . "\n"
            . '            $realInstanceReflection->getName(),' . "\n"
            . '            $' . $nameParameter . ',' . "\n"
            . '            $backtrace[0][\'file\'],' . "\n"
            . '            $backtrace[0][\'line\']' . "\n"
            . '        ),' . "\n"
            . '        \E_USER_NOTICE' . "\n"
            . '    );' . "\n";
    }

    /**
     * Defines whether the given operation produces a reference.
     *
     * Note: if the object is a wrapper, the wrapped instance is accessed directly. If the object
     * is a ghost or the proxy has no wrapper, then an instance of the parent class is created via
     * on-the-fly unserialization
     *
     * @phpstan-param self::OPERATION_* $operationType
     */
    private static function getByRefReturnValue(string $operationType): string
    {
        return $operationType === self::OPERATION_GET ? '& ' : '';
    }

    /**
     * Retrieves the logic to fetch the object on which access should be attempted
     */
    private static function getTargetObject(?PropertyGenerator $valueHolder = null): string
    {
        if ($valueHolder) {
            return '$this->' . $valueHolder->getName();
        }

        return '$realInstanceReflection->newInstanceWithoutConstructor()';
    }

    /**
     * @phpstan-param self::OPERATION_* $operationType
     *
     * @throws InvalidArgumentException
     */
    private static function getOperation(string $operationType, string $nameParameter): string
    {
        if ($operationType === self::OPERATION_GET) {
            return 'return $targetObject->$' . $nameParameter . ';';
        }

        if ($operationType === self::OPERATION_ISSET) {
            return 'return isset($targetObject->$' . $nameParameter . ');';
        }

        /* @phpstan-ignore-next-line */
        throw new InvalidArgumentException(sprintf('Invalid operation "%s" provided', $operationType));
    }

    /**
     * Generates code to bind operations to the parent scope
     */
    private static function generateScopeReBind(): string
    {
        return <<<'PHP'
$backtrace = debug_backtrace(true, 2);
$scopeObject = $backtrace[1]['object'] ?? new \ProxyManager\Stub\EmptyClassStub();
if ($scopeObject instanceof \Reflector) {
    $scopeObject = new \ProxyManager\Stub\EmptyClassStub();
}
$accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));

PHP;
    }
}
