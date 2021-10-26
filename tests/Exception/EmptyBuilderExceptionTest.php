<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Exception\EmptyBuilderException;

class EmptyBuilderExceptionTest extends TestCase
{
    public function testShouldHaveCode0(): void
    {
        $ex = new EmptyBuilderException();
        self::assertEquals(0, $ex->getCode());
    }
}
