<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;

use function assert;

class ServiceContainerArgumentValueResolver implements ArgumentValueResolverInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function supports(ReflectionParameter $parameter): bool
    {
        $parameterType = $parameter->getType();

        return $parameterType instanceof ReflectionNamedType &&
            (! $parameterType->isBuiltin()) &&
            $this->container->has($parameterType->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(ReflectionParameter $parameter): iterable
    {
        $parameterType = $parameter->getType();
        assert($parameterType instanceof ReflectionNamedType);

        yield $this->container->get($parameterType->getName());
    }
}
