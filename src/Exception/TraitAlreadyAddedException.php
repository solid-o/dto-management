<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use LogicException;

class TraitAlreadyAddedException extends LogicException implements ProxyBuilderExceptionInterface
{
}
