<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures;

trait FooTrait
{
    private function methodToReplace(): string
    {
        return __CLASS__;
    }

    private function fooMethod(): string
    {
        return __METHOD__;
    }

    private function foo2Method(): string
    {
        return __METHOD__;
    }
}
