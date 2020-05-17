<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

use PhpParser\Error;
use PhpParser\ParserFactory;
use Solido\DtoManagement\Exception\InvalidSyntaxException;

final class Util
{
    public static function assertValidPhpCode(string $code): void
    {
        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);

        try {
            $parser->parse('<?php ' . $code);
        } catch (Error $e) {
            throw new InvalidSyntaxException($e->getMessage(), 0, $e);
        }
    }
}
