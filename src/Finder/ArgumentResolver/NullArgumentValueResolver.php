<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionParameter;

class NullArgumentValueResolver implements ArgumentValueResolverInterface
{
    public function supports(ReflectionParameter $parameter): bool
    {
        return $parameter->allowsNull() && ! $parameter->isVariadic();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter): iterable
    {
        yield null;
    }
}
