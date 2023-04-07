<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionClass;
use Solido\DtoManagement\Exception\RuntimeException;

use function get_debug_type;
use function Safe\sprintf;

class ArgumentResolver implements ArgumentResolverInterface
{
    /** @var ArgumentValueResolverInterface[] */
    private array $resolvers;

    /** @param ArgumentValueResolverInterface[] $resolvers */
    public function __construct(array $resolvers)
    {
        $this->resolvers = (static fn (ArgumentValueResolverInterface ...$val) => $val)(...$resolvers);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(string $className, string $method): iterable
    {
        $arguments = [];
        $reflectionClass = new ReflectionClass($className);
        $reflector = $method === '__construct' ? $reflectionClass->getConstructor() : $reflectionClass->getMethod($method);
        if ($reflector === null) {
            return $arguments;
        }

        foreach ($reflector->getParameters() as $parameter) {
            $hasDefault = $parameter->isDefaultValueAvailable();
            $defaultValue = $hasDefault ? $parameter->getDefaultValue() : null;
            $argument = new Argument($className, $parameter->getName(), $parameter->getType(), $hasDefault, $defaultValue, $parameter->isVariadic(), $parameter->allowsNull());

            foreach ($this->resolvers as $resolver) {
                if (! $resolver->supports($argument)) {
                    continue;
                }

                $resolved = $resolver->resolve($argument);

                $atLeastOne = false;
                foreach ($resolved as $append) {
                    $atLeastOne = true;
                    $arguments[] = $append;
                }

                if (! $atLeastOne) {
                    throw new RuntimeException(sprintf('"%s::resolve()" must yield at least one value.', get_debug_type($resolver)));
                }

                // continue to the next argument
                continue 2;
            }

            throw new RuntimeException(sprintf('"%s::%s()" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.', $className, $method, $parameter->getName()));
        }

        return $arguments;
    }
}
