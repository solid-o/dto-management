<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Builder;

use PhpParser\Error;
use PhpParser\ParserFactory;
use Solido\DtoManagement\Exception\InvalidSyntaxException;

use function method_exists;

final class Util
{
    public static function assertValidPhpCode(string $code): void
    {
        if (method_exists(ParserFactory::class, 'createForHostVersion')) {
            $parser = (new ParserFactory())->createForHostVersion();
        } else {
            /** @phpstan-ignore-next-line */
            $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        }

        try {
            $parser->parse('<?php ' . $code);
        } catch (Error $e) {
            throw new InvalidSyntaxException($e->getMessage(), 0, $e);
        }
    }
}
