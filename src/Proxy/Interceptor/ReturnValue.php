<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Interceptor;

class ReturnValue
{
    /** @var mixed */
    private $value;

    /** @param mixed $value */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /** @return mixed */
    public function getValue()
    {
        return $this->value;
    }
}
