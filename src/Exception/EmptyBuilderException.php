<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use RuntimeException;
use Throwable;

class EmptyBuilderException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Proxy builder is empty, aborting proxy generation.', 0, $previous);
    }
}
