<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures\ServicedModel\v1\v1_2;

use Solido\DtoManagement\Tests\Fixtures\ServicedModel\Interfaces\FooInterface;

class Foo implements FooInterface
{
    public ?string $user;

    public function __construct(?string $user)
    {
        $this->user = $user;
    }
}
