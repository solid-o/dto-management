<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionType;

class Argument
{
    public function __construct(
        private readonly string $className,
        private readonly string $parameterName,
        private readonly ReflectionType|null $parameterType,
        private readonly bool $hasDefault,
        private readonly mixed $defaultValue,
        private readonly bool $isVariadic,
        private readonly bool $allowsNull,
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getParameterType(): ReflectionType|null
    {
        return $this->parameterType;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }

    public function allowsNull(): bool
    {
        return $this->allowsNull;
    }
}
