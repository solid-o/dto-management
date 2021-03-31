<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionFunctionAbstract;
use ReflectionMethod;
use Solido\DtoManagement\Exception\RuntimeException;

use function get_debug_type;
use function Safe\sprintf;

class ArgumentResolver implements ArgumentResolverInterface
{
    /** @var ArgumentValueResolverInterface[] */
    private array $resolvers;

    /**
     * @param ArgumentValueResolverInterface[] $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = (static fn (ArgumentValueResolverInterface ...$val) => $val)(...$resolvers);
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments(ReflectionFunctionAbstract $reflector): iterable
    {
        $arguments = [];

        foreach ($reflector->getParameters() as $parameter) {
            foreach ($this->resolvers as $resolver) {
                if (! $resolver->supports($parameter)) {
                    continue;
                }

                $resolved = $resolver->resolve($parameter);

                $atLeastOne = false;
                foreach ($resolved as $append) {
                    $atLeastOne = true;
                    $arguments[] = $append;
                }

                if (! $atLeastOne) {
                    throw new RuntimeException(sprintf('"%s::resolve()" must yield at least one value.', get_debug_type($resolver)));
                }

                // continue to the next controller argument
                continue 2;
            }

            if ($reflector instanceof ReflectionMethod) {
                $class = $reflector->getDeclaringClass();
                $name = $reflector->getName();

                $representative = sprintf('%s::%s()', $class->getName(), $name);
            } else {
                $representative = $reflector->getName();
            }

            throw new RuntimeException(sprintf('"%s" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.', $representative, $parameter->getName()));
        }

        return $arguments;
    }
}
