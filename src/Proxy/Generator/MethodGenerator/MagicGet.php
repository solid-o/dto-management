<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator\MethodGenerator;

use Laminas\Code\Generator\ParameterGenerator;
use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ReflectionClass;
use Solido\DtoManagement\Proxy\Generator\Util\PublicScopeSimulator;

use function Safe\sprintf;
use function str_replace;

class MagicGet extends MagicMethodGenerator
{
    public function __construct(ReflectionClass $originalClass, ValueHolderProperty $valueHolder, PublicPropertiesMap $publicProperties)
    {
        parent::__construct($originalClass, '__get', [new ParameterGenerator('name')]);

        $parent = GetMethodIfExists::get($originalClass, '__get');
        $valueHolderName = $valueHolder->getName();

        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '') . "@param string \$name\n");

        $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
            PublicScopeSimulator::OPERATION_GET,
            'name',
            $valueHolder,
            'returnValue',
        );

        if (! $publicProperties->isEmpty()) {
            $callParent = str_replace("\n", "\n    ", $callParent);

            $callParent = sprintf('
if (isset(self::$%1$s[$name])) {
    $returnValue = & $this->%2$s->$name;
} else {
    %3$s
}

', $publicProperties->getName(), $valueHolderName, $callParent);
        }

        $body = $callParent . "\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
