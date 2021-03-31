<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionFunctionAbstract;

interface ArgumentResolverInterface
{
    /**
     * Returns the arguments to pass to the given method.
     *
     * @return iterable<mixed>
     */
    public function getArguments(ReflectionFunctionAbstract $reflector): iterable;
}
