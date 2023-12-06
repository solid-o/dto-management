<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

/**
 * Represents a piece of code to be added as a wrapper to another method.
 */
class Wrapper
{
    public function __construct(private string $head, private string $tail)
    {
        Util::assertValidPhpCode($head . "\nassert(true);\n" . $tail);
    }

    /**
     * Gets the php code to be executed.
     */
    public function getCode(string $body): string
    {
        return $this->head . "\n" .
            $body . "\n" . $this->tail;
    }
}
