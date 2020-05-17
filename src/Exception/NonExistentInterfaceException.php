<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use LogicException;

class NonExistentInterfaceException extends LogicException implements ProxyBuilderExceptionInterface
{
}
