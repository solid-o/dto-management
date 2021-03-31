<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionParameter;

class DefaultValueArgumentValueResolver implements ArgumentValueResolverInterface
{
    public function supports(ReflectionParameter $parameter): bool
    {
        return $parameter->isDefaultValueAvailable() && ! $parameter->isVariadic();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter): iterable
    {
        yield $parameter->getDefaultValue();
    }
}
