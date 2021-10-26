<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Generator\MethodGenerator;

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\Util\Properties;
use ReflectionClass;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Generator\MethodGenerator\Constructor;
use PHPUnit\Framework\TestCase;

class ConstructorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|ProxyBuilder
     */
    private ObjectProphecy $builder;
    private Constructor $constructor;

    protected function setUp(): void
    {
        $this->builder = $this->prophesize(ProxyBuilder::class);
        $this->builder->properties = Properties::fromReflectionClass(new ReflectionClass(TestConstructorConcrete::class));
        $this->builder->getPropertyInterceptors('property1')->willReturn([new \stdClass()]);
        $this->builder->getPropertyInterceptors('property3')->willReturn([new \stdClass()]);
        $this->builder->getPropertyInterceptors(Argument::any())->willReturn([]);
    }

    public function testShouldGenerateConstructorCorrectly(): void
    {
        $class = new ReflectionClass(TestConstructorConcrete::class);
        $this->builder->getConstructorCode()->willReturn('');
        $this->constructor = Constructor::generateMethod($class, new ValueHolderProperty($class), $this->builder->reveal());
        self::assertStringMatchesFormat(<<<'EOF'
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->valueHolder%a = new class extends \stdClass {
            public $property1 = 'default';
            public $property3 = NULL;
        };
        unset($this->property1, $this->property3);

        parent::__construct();
    }
EOF, $this->constructor->generate());
    }

    public function testShouldAddCustomConstructorCode(): void
    {
        $class = new ReflectionClass(TestConstructorConcrete::class);
        $this->builder->getConstructorCode()->willReturn('$this->fooValue = \'foo\';');
        $this->constructor = Constructor::generateMethod($class, new ValueHolderProperty($class), $this->builder->reveal());
        self::assertStringMatchesFormat(<<<'EOF'
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        $this->valueHolder%a = new class extends \stdClass {
            public $property1 = 'default';
            public $property3 = NULL;
        };
        unset($this->property1, $this->property3);

        parent::__construct();
        $this->fooValue = 'foo';
    }
EOF, $this->constructor->generate());
    }

    public function testShouldCallParentWithVariadicArgs(): void
    {
        $class = new ReflectionClass(TestConstructorVariadic::class);
        $this->builder = $this->prophesize(ProxyBuilder::class);
        $this->builder->properties = Properties::fromReflectionClass($class);
        $this->builder->getPropertyInterceptors('property1')->willReturn([new \stdClass()]);
        $this->builder->getPropertyInterceptors('property3')->willReturn([new \stdClass()]);
        $this->builder->getPropertyInterceptors(Argument::any())->willReturn([]);
        $this->builder->getConstructorCode()->willReturn('');

        $this->constructor = Constructor::generateMethod($class, new ValueHolderProperty($class), $this->builder->reveal());
        self::assertStringMatchesFormat(<<<'EOF'
    /**
     * {@inheritDoc}
     */
    public function __construct(string ... $properties)
    {
        $this->valueHolder%a = new class extends \stdClass {
            public $property1 = 'default';
            public $property3 = NULL;
        };
        unset($this->property1, $this->property3);

        parent::__construct(...$properties);
    }
EOF, $this->constructor->generate());
    }
}

class TestConstructorConcrete
{
    public $property1 = 'default';
    public $property2;
    public $property3;
    public $property4;

    public function __construct()
    {
    }
}

class TestConstructorVariadic
{
    public $property1 = 'default';
    public $property2;
    public $property3;
    public $property4;

    public function __construct(string ...$properties)
    {
    }
}
