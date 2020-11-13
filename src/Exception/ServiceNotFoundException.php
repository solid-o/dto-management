<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Exception;

use InvalidArgumentException;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

use function count;
use function implode;
use function Safe\sprintf;

/**
 * This exception is thrown when a non-existent service is requested.
 */
class ServiceNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    private string $id;
    private ?string $sourceId;

    /** @var string[] */
    private array $alternatives;

    /**
     * @param string[] $alternatives
     */
    public function __construct(string $id, ?string $sourceId = null, ?Throwable $previous = null, array $alternatives = [], ?string $msg = null)
    {
        if ($msg === null && $sourceId === null) {
            $msg = sprintf('You have requested a non-existent service "%s".', $id);
        } elseif ($msg === null) {
            $msg = sprintf('The service "%s" has a dependency on a non-existent service "%s".', $sourceId, $id);
        }

        $countAlternatives = count($alternatives);
        if ($countAlternatives > 0) {
            $msg .= sprintf(
                ' Did you mean %s: "%s"?',
                $countAlternatives > 1 ? 'one of these' : 'this',
                implode('", "', $alternatives)
            );
        }

        parent::__construct($msg, 0, $previous);

        $this->id = $id;
        $this->sourceId = $sourceId;
        $this->alternatives = $alternatives;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSourceId(): ?string
    {
        return $this->sourceId;
    }

    /**
     * @return string[]
     */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }
}
