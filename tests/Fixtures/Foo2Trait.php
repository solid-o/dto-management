<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures;

trait Foo2Trait
{
    private function methodToReplace(): string
    {
        return __CLASS__;
    }
}
