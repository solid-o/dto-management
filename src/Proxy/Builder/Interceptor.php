<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

/**
 * Represents a piece of code to be executed before property set and marked methods.
 */
class Interceptor
{
    public function __construct(private string $code)
    {
        Util::assertValidPhpCode($code);
    }

    /**
     * Gets the php code to be executed.
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
