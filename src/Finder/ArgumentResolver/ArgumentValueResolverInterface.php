<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionParameter;

interface ArgumentValueResolverInterface
{
    /**
     * Whether this resolver can resolve the value for the given argument.
     */
    public function supports(ReflectionParameter $parameter): bool;

    /**
     * Returns the possible value(s).
     *
     * @return iterable<mixed>
     */
    public function resolve(ReflectionParameter $parameter): iterable;
}
