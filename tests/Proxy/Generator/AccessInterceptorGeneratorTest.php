<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Generator;

use Laminas\Code\Generator\ClassGenerator;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Generator\AccessInterceptorGenerator;
use PHPUnit\Framework\TestCase;

class AccessInterceptorGeneratorTest extends TestCase
{
    public function testShouldGenerateProxyCorrectly(): void
    {
        $generator = new AccessInterceptorGenerator([new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addPropertyInterceptor('interceptedProperty', new Interceptor('// Do nothing'));
                $proxyBuilder->addMethodInterceptor('interceptedMethod', new Interceptor("// Do nothing\n// but multiline"));
                $proxyBuilder->addMethodWrapper('wrappedMethod', new Wrapper('try {', '} finally {}'));
            }
        }]);

        $class = new \ReflectionClass(TestInterceptedClass::class);
        $generator->generate($class, $classGenerator = new ClassGenerator('Proxy'));
        self::assertStringMatchesFormat(<<<'EOF'
use Solido\DtoManagement\Proxy\Interceptor\ReturnValue;

class Proxy extends \Solido\DtoManagement\Tests\Proxy\Generator\TestInterceptedClass implements \Solido\DtoManagement\Proxy\ProxyInterface
{
    /**
     * @var \Solido\DtoManagement\Tests\Proxy\Generator\TestInterceptedClass|null wrapped object, if the proxy is initialized
     */
    private $valueHolder%a = null;

    /**
     * @var bool[] map of public properties of the parent class
     */
    private static $publicProperties%a = [
        'interceptedProperty' => true,
    ];

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->valueHolder%a = new class extends \stdClass {
            public $interceptedProperty = 'default';
        };
        unset($this->interceptedProperty);

        parent::__construct();
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function interceptedMethod($obj) : void
    {
        $returnValue = (function () use (&$obj) {
            // Do nothing
            // but multiline
        })();

        ;
        parent::interceptedMethod($obj);
    }

    /**
     * {@inheritDoc}
     */
    public function wrappedMethod() : void
    {
        try {
        ;
        parent::wrappedMethod();
        } finally {}
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return mixed
     */
    public function __set($name, $value)
    {
        switch($name) {
            case 'interceptedProperty': 
                
                    $returnValue = (function () use (&$value) {
                    // Do nothing
                    })();
                    
                    
                    if ($returnValue instanceof ReturnValue) {
                        return $returnValue->getValue();
                    }
                    
                    ;
                break;

        }


        if (isset(self::$publicProperties%a[$name])) {
            $returnValue = ($this->valueHolder%a->$name = $value);
        } else {
            $targetObject = $this->valueHolder%a;
            $accessor = function & () use ($targetObject, $name, $value) {
                $targetObject->$name = $value;
                return $targetObject->$name;
            };
            
            $backtrace = \debug_backtrace(true);
            $scopeObject = isset($backtrace[1]['object']) ? $backtrace[1]['object'] : new \ProxyManager\Stub\EmptyClassStub();
            $accessor = $accessor->bindTo($scopeObject, \get_class($scopeObject));
            $returnValue = & $accessor();
            
        }


        return $returnValue;
    }

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

    /**
     * @param string $name
     */
    public function __isset($name)
    {
        $realInstanceReflection = new \ReflectionClass(get_parent_class($this));

        if (! $realInstanceReflection->hasProperty($name)) {
            $targetObject = $this->valueHolder%a;

            return isset($targetObject->$name);
        }

        $targetObject = $this->valueHolder%a;
        $accessor = function () use ($targetObject, $name) {
            return isset($targetObject->$name);
        };
        $backtrace = debug_backtrace(true, 2);
        $scopeObject = $backtrace[1]['object'] ?? new \ProxyManager\Stub\EmptyClassStub();
        if ($scopeObject instanceof \Reflector) {
            $scopeObject = new \ProxyManager\Stub\EmptyClassStub();
        }
        $accessor = $accessor->bindTo($scopeObject, get_class($scopeObject));
        $returnValue = $accessor();
        return $returnValue;
    }
}
EOF, $classGenerator->generate());
    }
}

class TestInterceptedClass
{
    public $interceptedProperty = 'default';

    public function __construct()
    {
    }

    public function normalMethod(): void
    {
    }

    public function interceptedMethod(object $obj): void
    {
    }

    public function wrappedMethod(): void
    {
    }
}
