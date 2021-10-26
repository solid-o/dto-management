<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\Builder;

use Solido\DtoManagement\Exception\InvalidSyntaxException;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use PHPUnit\Framework\TestCase;

class WrapperTest extends TestCase
{
    public function testShouldEmitMethodCodeCorrectly(): void
    {
        $wrapper = new Wrapper('try {', '} catch (\Exception $e) {}');
        self::assertEquals(<<<'EOF'
try {
$this->foo = 42;
} catch (\Exception $e) {}
EOF, $wrapper->getCode('$this->foo = 42;'));
    }

    public function testShouldThrowIfHeadContainsInvalidCode(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $this->expectExceptionMessage('Syntax error, unexpected T_STRING on line 2');

        new Wrapper('trix', '} catch (\Exception $e) {}');
    }

    public function testShouldThrowIfTailContainsInvalidCode(): void
    {
        $this->expectException(InvalidSyntaxException::class);
        $this->expectExceptionMessage('Cannot use try without catch or finally on line 1');

        new Wrapper('try {', '} catches (\Exception $e) {}');
    }
}
