<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

interface ArgumentResolverInterface
{
    /**
     * Returns the arguments to pass to the given method.
     *
     * @phpstan-param class-string $className
     *
     * @return iterable<mixed>
     */
    public function getArguments(string $className, string $method): iterable;
}
