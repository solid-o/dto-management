<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Builder;

use Solido\DtoManagement\Exception\InvalidSyntaxException;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use PHPUnit\Framework\TestCase;

class InterceptorTest extends TestCase
{
    public function testShouldThrowOnInvalidPhpCode(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        new Interceptor(
'xx foo
if (non closed {
');
    }

    public function testShouldReturnCode(): void
    {
        $code = 'if ($condition) {
    return SetValue(234);
}
';

        $interceptor = new Interceptor($code);
        self::assertEquals($code, $interceptor->getCode());
    }
}
