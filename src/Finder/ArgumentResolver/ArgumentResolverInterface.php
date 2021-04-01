<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

interface ArgumentResolverInterface
{
    /**
     * Returns the arguments to pass to the given method.
     *
     * @return iterable<mixed>
     *
     * @phpstan-param class-string $className
     */
    public function getArguments(string $className, string $method): iterable;
}
