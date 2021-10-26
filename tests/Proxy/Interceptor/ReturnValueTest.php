<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Interceptor;

use Solido\DtoManagement\Proxy\Interceptor\ReturnValue;
use PHPUnit\Framework\TestCase;

class ReturnValueTest extends TestCase
{
    public function testValueIsAccessible(): void
    {
        $value = new ReturnValue('test');
        self::assertEquals('test', $value->getValue());
    }
}
