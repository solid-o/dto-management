<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

class DefaultValueArgumentValueResolver implements ArgumentValueResolverInterface
{
    public function supports(Argument $argument): bool
    {
        return $argument->hasDefault() && ! $argument->isVariadic();
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Argument $argument): iterable
    {
        yield $argument->getDefaultValue();
    }
}
