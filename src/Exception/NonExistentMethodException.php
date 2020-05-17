<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use LogicException;

class NonExistentMethodException extends LogicException implements ProxyBuilderExceptionInterface
{
}
