<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Generator\MethodGenerator;

use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\Properties;
use Solido\DtoManagement\Proxy\Generator\MethodGenerator\MagicGet;
use PHPUnit\Framework\TestCase;

class MagicGetTest extends TestCase
{
    public function testShouldGenerateMagicGetCodeCorrectly(): void
    {
        $class = new \ReflectionClass(TestMagicGetConcrete::class);
        $properties = new PublicPropertiesMap(Properties::fromReflectionClass($class));
        $valueHolder = new ValueHolderProperty($class);

        $generator = new MagicGet($class, $valueHolder, $properties);
        self::assertStringMatchesFormat(<<<'EOF'
    /**
     * @param string $name
     */
    public function & __get($name)
    {
        if (isset(self::$publicProperties%a[$name])) {
            $returnValue = & $this->valueHolder%a->$name;
        } else {
            $realInstanceReflection = new \ReflectionClass(get_parent_class($this));
            
            if (! $realInstanceReflection->hasProperty($name)) {
                $targetObject = $this->valueHolder%a;
            
                $backtrace = debug_backtrace(false, 1);
                trigger_error(
                    sprintf(
                        'Undefined property: %s::$%s in %s on line %s',
                        $realInstanceReflection->getName(),
                        $name,
                        $backtrace[0]['file'],
                        $backtrace[0]['line']
                    ),
                    \E_USER_NOTICE
                );
                return $targetObject->$name;
            }
            
            $targetObject = $this->valueHolder%a;
            $accessor = function & () use ($targetObject, $name) {
                return $targetObject->$name;
            };
            $backtrace = debug_backtrace(true, 2);
            $scopeObject = $backtrace[1]['object'] ?? new \ProxyManager\Stub\EmptyClassStub();
            if ($scopeObject instanceof \Reflector) {
                $scopeObject = new \ProxyManager\Stub\EmptyClassStub();
            }
            $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
            $returnValue = & $accessor();
        }


        return $returnValue;
    }
EOF, $generator->generate());
    }
}

class TestMagicGetConcrete
{
    public $property1 = 'default';
    public $property2;
    public $property3;
    public $property4;

    public function __construct()
    {
    }
}
