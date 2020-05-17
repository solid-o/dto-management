<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator;

use Closure;
use InvalidArgumentException;
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
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Interceptor\ReturnValue;
use function array_map;
use function assert;
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

        $publicPropertiesMap = new PublicPropertiesMap($builder->properties);

        $classGenerator->setExtendedClass($originalClass->getName());
        $classGenerator->setImplementedInterfaces($builder->getInterfaces());
        $classGenerator->addPropertyFromGenerator($valueHolder = new ValueHolderProperty($originalClass));

        foreach ($builder->getExtraProperties() as $property) {
            $classGenerator->addPropertyFromGenerator($property);
        }

        $classGenerator->addPropertyFromGenerator($publicPropertiesMap);
        $classGenerator->addMethodFromGenerator(MethodGenerator\Constructor::generateMethod($originalClass, $valueHolder, $builder));

        $classGenerator->addUse(ReturnValue::class);

        foreach (ProxiedMethodsFilter::getProxiedMethods($originalClass) as $proxiedMethod) {
            $interceptors = $builder->getMethodInterceptors($proxiedMethod->getName());
            if (count($interceptors) === 0) {
                continue;
            }

            if ($proxiedMethod->isFinal()) {
                throw new InvalidArgumentException('Method "' . $proxiedMethod->getName() . '" is marked as final and cannot be proxied.');
            }

            $classGenerator->addMethodFromGenerator($this->generateInterceptedMethod($proxiedMethod, $interceptors));
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
     */
    private function generateInterceptedMethod(ReflectionMethod $originalMethod, array $interceptors): LaminasMethodGenerator
    {
        $method = LaminasMethodGenerator::fromReflection(new MethodReflection($originalMethod->getDeclaringClass()->getName(), $originalMethod->getName()));
        foreach ($method->getParameters() as $parameter) {
            Closure::bind(function () {
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

        $returnType = $originalMethod->getReturnType();
        assert($returnType === null || $returnType instanceof ReflectionNamedType);

        if ($returnType !== null && $returnType->getName() === 'void') {
            $return = '';
        }

        $interceptorCode = array_map(
            static fn (Interceptor $interceptor) =>
                sprintf('(function () use (%s) {
%s
})()', implode(', ', $interceptorParams), $interceptor->getCode()),
            $interceptors
        );

        $body = implode(";\n", array_map(static fn (string $line) => str_replace("\n", "\n        ", $line), $interceptorCode))
            . sprintf(";\n%sparent::%s(%s);", $return, $originalMethod->getName(), $forwardedParams);

        $method->setDocBlock('{@inheritDoc}');
        $method->setBody($body);

        return $method;
    }
}
