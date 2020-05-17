<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Factory;

use ProxyManager\Configuration as BaseConfiguration;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;

class Configuration extends BaseConfiguration
{
    /** @var ExtensionInterface[] */
    protected array $extensions = [];

    public function addExtension(ExtensionInterface $extension): void
    {
        $this->extensions[] = $extension;
    }

    /**
     * @return ExtensionInterface[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
