<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Extension;

use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;

/**
 * Represents a proxy builder extension point.
 * It is used when building proxies, before the code is emitted and compiled.
 */
interface ExtensionInterface
{
    /**
     * Extends a proxy.
     */
    public function extend(ProxyBuilder $proxyBuilder): void;
}
