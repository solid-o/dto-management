<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Generator;

use Solido\DtoManagement\Proxy\Generator\ProxiedMethodsFilter;
use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Tests\Fixtures\ProxableClass;

class ProxiedMethodsFilterTest extends TestCase
{
    public function testShouldFilterMagicMethodsByDefault(): void
    {
        $methods = ProxiedMethodsFilter::getProxiedMethods(new \ReflectionClass(TestFilterProxableClass::class));
        $methods = array_column($methods, 'name');
        self::assertEquals(['publicMethod', 'protectedMethod', 'publicWithVariadic'], $methods);
    }

    public function testShouldFilterExcludedMethod(): void
    {
        $methods = ProxiedMethodsFilter::getProxiedMethods(new \ReflectionClass(TestFilterProxableClass::class), ['FINALmethod', 'PuBlIcMeThOd']);
        $methods = array_column($methods, 'name');
        self::assertEquals(['__get', '__set', 'protectedMethod', 'publicWithVariadic'], $methods);
    }
}

class TestFilterProxableClass extends ProxableClass
{
    public function __get($name)
    {
        // TODO: Implement __get() method.
    }

    public function __set($name, $value)
    {
        // TODO: Implement __set() method.
    }

    private function privateMethod(): void
    {
    }

    public static function pubStaticMethod(): void
    {
    }
}
