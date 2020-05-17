<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Factory;

use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
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
        parent::__construct($configuration);
    }

    /**
     * Change visibility of generateProxy method (protected -> public).
     *
     * {@inheritdoc}
     */
    public function generateProxy(string $className, array $proxyOptions = []): string
    {
        return parent::generateProxy($className, $proxyOptions);
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
