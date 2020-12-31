<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures;

class Php8ProxableClass extends ProxableClass
{
    public string|int $unionProperty;

    public function unionTypedMethod(string|int $parameter): bool|self
    {
    }
}
