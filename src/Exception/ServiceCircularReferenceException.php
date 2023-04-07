<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Throwable;

use function implode;
use function Safe\sprintf;

class ServiceCircularReferenceException extends RuntimeException implements ContainerExceptionInterface
{
    /** @var string[] */
    private array $path;
    private string $serviceId;

    /** @param string[] $path */
    public function __construct(string $serviceId, array $path, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Circular reference detected for service "%s", path: "%s".', $serviceId, implode(' -> ', $path)), 0, $previous);

        $this->path = $path;
        $this->serviceId = $serviceId;
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
