<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function count;
use function implode;
use function sprintf;

/**
 * This exception is thrown when a non-existent service is requested.
 */
class ServiceNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    private string $version;

    /** @param string[] $alternatives */
    public function __construct(private string $id, string $version, private string|null $sourceId = null, Throwable|null $previous = null, private array $alternatives = [], string|null $msg = null)
    {
        $version = $version ?: 'latest';
        if ($msg === null && $sourceId === null) {
            $msg = sprintf('You have requested a non-existent version "%s" for service "%s".', $version, $id);
        } elseif ($msg === null) {
            $msg = sprintf('The version "%s" has a dependency on a non-existent version "%s" for service "%s".', $sourceId, $version, $id);
        }

        $countAlternatives = count($alternatives);
        if ($countAlternatives > 0) {
            $msg .= sprintf(
                ' Did you mean %s: "%s"?',
                $countAlternatives > 1 ? 'one of these' : 'this',
                implode('", "', $alternatives),
            );
        }

        parent::__construct($msg, 0, $previous);

        $this->version = $version;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getSourceId(): string|null
    {
        return $this->sourceId;
    }

    /** @return string[] */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }
}
