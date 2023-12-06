<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Interceptor;

class ReturnValue
{
    public function __construct(private mixed $value)
    {
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
