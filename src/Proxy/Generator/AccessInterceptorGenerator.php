<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator;

use Closure;
use Laminas\Code\Generator\ClassGenerator;
use Laminas\Code\Generator\MethodGenerator as LaminasMethodGenerator;
use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Reflection\MethodReflection;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Solido\DtoManagement\Exception\EmptyBuilderException;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Builder\Wrapper;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Interceptor\ReturnValue;

use function array_map;
use function count;
use function implode;
use function Safe\sprintf;
use function str_replace;

class AccessInterceptorGenerator implements ProxyGeneratorInterface
{
    /** @var iterable<ExtensionInterface> */
    private iterable $extensions;

    /**
     * @param iterable<ExtensionInterface> $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(ReflectionClass $originalClass, ClassGenerator $classGenerator, array $options = []): void
    {
        $builder = new ProxyBuilder($originalClass);

        foreach ($this->extensions as $extension) {
            $extension->extend($builder);
        }

        if ($builder->empty()) {
            throw new EmptyBuilderException();
        }

        $publicPropertiesMap = new PublicPropertiesMap($builder->properties);

        $classGenerator->setExtendedClass($originalClass->getName());
        $classGenerator->setImplementedInterfaces($builder->getInterfaces());
        $classGenerator->addPropertyFromGenerator($valueHolder = new ValueHolderProperty($originalClass));
        foreach ($builder->getTraits() as $trait => $props) {
            $reflection = new ReflectionClass($trait);
            $classGenerator->addUse($trait);

            $classGenerator->addTrait($reflection->getShortName());
            foreach ($props['aliases'] as $alias) {
                $classGenerator->addTraitAlias($alias['method'], $alias['alias'], $alias['visibility'] ?? null);
            }

            foreach ($props['overrides'] as $alias) {
                $classGenerator->addTraitOverride($alias['method'], $alias['traitToReplace']);
            }
        }

        foreach ($builder->getExtraProperties() as $property) {
            $classGenerator->addPropertyFromGenerator($property);
        }

        $classGenerator->addPropertyFromGenerator($publicPropertiesMap);
        $classGenerator->addMethodFromGenerator(MethodGenerator\Constructor::generateMethod($originalClass, $valueHolder, $builder));

        $classGenerator->addUse(ReturnValue::class);

        foreach (ProxiedMethodsFilter::getProxiedMethods($originalClass) as $proxiedMethod) {
            $interceptors = $builder->getMethodInterceptors($proxiedMethod->getName());
            $wrappers = $builder->getMethodWrappers($proxiedMethod->getName());
            if (count($interceptors) === 0 && count($wrappers) === 0) {
                continue;
            }

            $classGenerator->addMethodFromGenerator($this->generateInterceptedMethod($proxiedMethod, $interceptors, $wrappers));
        }

        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicSet($originalClass, $valueHolder, $publicPropertiesMap, $builder));
        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicGet($originalClass, $valueHolder, $publicPropertiesMap));
        $classGenerator->addMethodFromGenerator(new MethodGenerator\MagicIsset($originalClass, $valueHolder));

        foreach ($builder->getExtraMethods() as $method) {
            $classGenerator->addMethodFromGenerator($method);
        }
    }

    /**
     * @param Interceptor[] $interceptors
     * @param Wrapper[] $wrappers
     */
    private function generateInterceptedMethod(ReflectionMethod $originalMethod, array $interceptors, array $wrappers): LaminasMethodGenerator
    {
        $method = LaminasMethodGenerator::fromReflection(new MethodReflection($originalMethod->getDeclaringClass()->getName(), $originalMethod->getName()));
        foreach ($method->getParameters() as $parameter) {
            Closure::bind(function (): void {
                $this->type = null;
            }, $parameter, ParameterGenerator::class)();
        }

        $forwardedParams = [];
        $interceptorParams = [];

        foreach ($originalMethod->getParameters() as $parameter) {
            $forwardedParams[] = ($parameter->isVariadic() ? '...' : '') . '$' . $parameter->getName();
            $interceptorParams[] = '&$' . $parameter->getName();
        }

        $forwardedParams = implode(', ', $forwardedParams);
        $return = 'return ';
        $returnValue = 'if ($returnValue instanceof ReturnValue) { return $returnValue->getValue(); }';

        $returnType = $originalMethod->getReturnType();
        if ($returnType instanceof ReflectionNamedType && $returnType->getName() === 'void') {
            $return = '';
            $returnValue = '';
        }

        $interceptorCode = array_map(
            static fn (Interceptor $interceptor) => sprintf('$returnValue = (function ()%s {
%s
})();

%s', $forwardedParams ? ' use (' . implode(', ', $interceptorParams) . ')' : '', '    ' . str_replace("\n", "\n    ", $interceptor->getCode()), $returnValue),
            $interceptors
        );

        $body = implode("\n", $interceptorCode)
            . sprintf(";\n%sparent::%s(%s);", $return, $originalMethod->getName(), $forwardedParams);

        $method->setDocBlock('{@inheritDoc}');
        foreach ($wrappers as $wrapper) {
            $body = $wrapper->getCode($body);
        }

        $method->setBody($body);

        return $method;
    }
}
