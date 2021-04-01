<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Finder\ArgumentResolver;

use ReflectionType;

class Argument
{
    private string $className;
    private string $parameterName;
    private ?ReflectionType $parameterType;
    private bool $hasDefault;

    /** @var mixed */
    private $defaultValue;
    private bool $isVariadic;
    private bool $allowsNull;

    /**
     * @param mixed $defaultValue
     */
    public function __construct(string $className, string $parameterName, ?ReflectionType $parameterType, bool $hasDefault, $defaultValue, bool $isVariadic, bool $allowsNull)
    {
        $this->className = $className;
        $this->parameterName = $parameterName;
        $this->parameterType = $parameterType;
        $this->hasDefault = $hasDefault;
        $this->defaultValue = $defaultValue;
        $this->isVariadic = $isVariadic;
        $this->allowsNull = $allowsNull;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getParameterType(): ?ReflectionType
    {
        return $this->parameterType;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
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
