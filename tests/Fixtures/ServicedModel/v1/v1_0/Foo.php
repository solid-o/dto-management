<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures\ServicedModel\v1\v1_0;

use Solido\DtoManagement\Tests\Fixtures\ServicedModel\Interfaces\FooInterface;

class Foo implements FooInterface
{
    public function __construct(User $user)
    {
        // Could not be instantiable by service registry
    }
}
