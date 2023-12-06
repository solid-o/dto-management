<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

class NullArgumentValueResolver implements ArgumentValueResolverInterface
{
    public function supports(Argument $argument): bool
    {
        return $argument->allowsNull() && ! $argument->isVariadic();
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Argument $argument): iterable
    {
        yield null;
    }
}
