<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;

use function assert;

class ServiceContainerArgumentValueResolver implements ArgumentValueResolverInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function supports(Argument $argument): bool
    {
        $parameterType = $argument->getParameterType();

        return $parameterType instanceof ReflectionNamedType &&
            (! $parameterType->isBuiltin()) &&
            $this->container->has($parameterType->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Argument $argument): iterable
    {
        $parameterType = $argument->getParameterType();
        assert($parameterType instanceof ReflectionNamedType);

        yield $this->container->get($parameterType->getName());
    }
}
