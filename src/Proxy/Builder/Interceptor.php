<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

/**
 * Represents a piece of code to be executed before property set and marked methods.
 */
class Interceptor
{
    private string $code;

    public function __construct(string $code)
    {
        Util::assertValidPhpCode($code);

        $this->code = $code;
    }

    /**
     * Gets the php code to be executed.
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
