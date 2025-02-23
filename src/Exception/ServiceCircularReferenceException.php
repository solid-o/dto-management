<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;

use function implode;
use function sprintf;

class ServiceCircularReferenceException extends RuntimeException implements ContainerExceptionInterface
{
    /** @param string[] $path */
    public function __construct(private string $serviceId, private array $path, Throwable|null $previous = null)
    {
        parent::__construct(sprintf('Circular reference detected for service "%s", path: "%s".', $serviceId, implode(' -> ', $path)), 0, $previous);
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }

    /** @return string[] */
    public function getPath(): array
    {
        return $this->path;
    }
}
