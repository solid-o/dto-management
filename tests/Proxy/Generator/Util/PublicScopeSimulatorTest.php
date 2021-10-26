<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Generator\Util;

use InvalidArgumentException;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use Solido\DtoManagement\Proxy\Generator\Util\PublicScopeSimulator;
use PHPUnit\Framework\TestCase;

class PublicScopeSimulatorTest extends TestCase
{
    public function testShouldGenerateGetAccessorCodeCorrectly(): void
    {
        self::assertStringMatchesFormat(<<<'EOF'
$realInstanceReflection = new \ReflectionClass(get_parent_class($this));

if (! $realInstanceReflection->hasProperty($value)) {
    $targetObject = $this->valueHolder%a;

    $backtrace = debug_backtrace(false, 1);
    trigger_error(
        sprintf(
            'Undefined property: %s::$%s in %s on line %s',
            $realInstanceReflection->getName(),
            $value,
            $backtrace[0]['file'],
            $backtrace[0]['line']
        ),
        \E_USER_NOTICE
    );
    return $targetObject->$value;
}

$targetObject = $this->valueHolder%a;
$accessor = function & () use ($targetObject, $value) {
    return $targetObject->$value;
};
$backtrace = debug_backtrace(true, 2);
$scopeObject = $backtrace[1]['object'] ?? new \ProxyManager\Stub\EmptyClassStub();
if ($scopeObject instanceof \Reflector) {
    $scopeObject = new \ProxyManager\Stub\EmptyClassStub();
}
$accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
$returnValue = & $accessor();
EOF, PublicScopeSimulator::getPublicAccessSimulationCode(
    PublicScopeSimulator::OPERATION_GET,
    'value',
    new ValueHolderProperty(new \ReflectionClass(TestPublicScopeSimulator::class)),
    'returnValue'
        ));
    }

    public function testShouldGenerateSetAccessorCodeCorrectly(): void
    {
        self::assertStringMatchesFormat(<<<'EOF'
$realInstanceReflection = new \ReflectionClass(get_parent_class($this));

if (! $realInstanceReflection->hasProperty($value)) {
    $targetObject = $this->valueHolder%a;

    return isset($targetObject->$value);
}

$targetObject = $this->valueHolder%a;
$accessor = function () use ($targetObject, $value) {
    return isset($targetObject->$value);
};
$backtrace = debug_backtrace(true, 2);
$scopeObject = $backtrace[1]['object'] ?? new \ProxyManager\Stub\EmptyClassStub();
if ($scopeObject instanceof \Reflector) {
    $scopeObject = new \ProxyManager\Stub\EmptyClassStub();
}
$accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
$returnValue = $accessor();
EOF, PublicScopeSimulator::getPublicAccessSimulationCode(
    PublicScopeSimulator::OPERATION_ISSET,
    'value',
    new ValueHolderProperty(new \ReflectionClass(TestPublicScopeSimulator::class)),
    'returnValue'
        ));
    }

    public function testShouldThrowOnInvalidOperation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operation "set" provided');

        PublicScopeSimulator::getPublicAccessSimulationCode(
            'set',
            'value',
            new ValueHolderProperty(new \ReflectionClass(TestPublicScopeSimulator::class)),
            'returnValue'
        );
    }
}

class TestPublicScopeSimulator
{
    public $property1 = 'default';
    public $property2;
    public $property3;
    public $property4;

    public function __construct()
    {
    }
}
