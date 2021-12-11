<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Factory;

use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use Solido\DtoManagement\Exception\EmptyBuilderException;
use Solido\DtoManagement\Proxy\Generator\AccessInterceptorGenerator;

use function assert;

class AccessInterceptorFactory extends AbstractBaseFactory
{
    private ?ProxyGeneratorInterface $generator = null;

    /**
     * Not so useless: overwrite configuration type hint.
     */
    public function __construct(?Configuration $configuration = null)
    {
        $configuration ??= new Configuration();

        parent::__construct($configuration);
    }

    /**
     * Change visibility of generateProxy method (protected -> public).
     *
     * {@inheritdoc}
     */
    public function generateProxy(string $className, array $proxyOptions = []): string
    {
        try {
            return parent::generateProxy($className, $proxyOptions);
        } catch (EmptyBuilderException $exception) { /* @phpstan-ignore-line */
            if ($proxyOptions['throw_empty'] ?? false) {
                throw $exception;
            }

            return $className;
        }
    }

    public function setGenerator(ProxyGeneratorInterface $generator): void
    {
        $this->generator = $generator;
    }

    protected function getGenerator(): ProxyGeneratorInterface
    {
        assert($this->configuration instanceof Configuration);

        return $this->generator ?? new AccessInterceptorGenerator($this->configuration->getExtensions());
    }
}
