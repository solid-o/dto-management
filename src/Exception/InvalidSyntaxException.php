<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use ParseError;

class InvalidSyntaxException extends ParseError implements ProxyBuilderExceptionInterface
{
}
