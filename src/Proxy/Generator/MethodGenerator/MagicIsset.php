<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator\MethodGenerator;

use Laminas\Code\Generator\ParameterGenerator;
use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolder\PropertyGenerator\ValueHolderProperty;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ReflectionClass;
use Solido\DtoManagement\Proxy\Generator\Util\PublicScopeSimulator;

class MagicIsset extends MagicMethodGenerator
{
    public function __construct(ReflectionClass $originalClass, ValueHolderProperty $valueHolder)
    {
        parent::__construct($originalClass, '__isset', [new ParameterGenerator('name')]);

        $parent = GetMethodIfExists::get($originalClass, '__isset');
        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '') . '@param string $name');

        $callParent = '$returnValue = & parent::__isset($name);';

        if (! $parent) {
            $callParent = PublicScopeSimulator::getPublicAccessSimulationCode(
                PublicScopeSimulator::OPERATION_ISSET,
                'name',
                $valueHolder,
                'returnValue',
            );
        }

        $body = $callParent . "\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
