<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\Generator\MethodGenerator;

use Laminas\Code\Generator\ParameterGenerator;
use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Generator\MagicMethodGenerator;
use ProxyManager\ProxyGenerator\PropertyGenerator\PublicPropertiesMap;
use ProxyManager\ProxyGenerator\Util\GetMethodIfExists;
use ReflectionClass;
use Solido\DtoManagement\Proxy\Builder\Interceptor;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use function array_map;
use function count;
use function implode;
use function Safe\sprintf;
use function str_replace;

class MagicSet extends MagicMethodGenerator
{
    private const INTERCEPTOR_SET_RETURN = '
if ($returnValue instanceof ReturnValue) {
    return $returnValue->getValue();
}
';

    public function __construct(ReflectionClass $originalClass, PropertyGenerator $valueHolder, PublicPropertiesMap $publicProperties, ProxyBuilder $proxyBuilder)
    {
        parent::__construct($originalClass, '__set', [new ParameterGenerator('name'), new ParameterGenerator('value')]);

        $parent = GetMethodIfExists::get($originalClass, '__set');
        $valueHolderName = $valueHolder->getName();

        $this->setDocBlock(($parent ? "{@inheritDoc}\n" : '') . "@param string \$name\n@param mixed \$value\n\n@return mixed");

        $callParent = sprintf('$targetObject = $this->%s;', $valueHolderName);
        $callParent .= '
$accessor = function & () use ($targetObject, $name, $value) {
    $targetObject->$name = $value;
    return $targetObject->$name;
};

$backtrace = \\debug_backtrace(true);
$scopeObject = isset($backtrace[1][\'object\']) ? $backtrace[1][\'object\'] : new \ProxyManager\Stub\EmptyClassStub();
$accessor = $accessor->bindTo($scopeObject, \\get_class($scopeObject));
$returnValue = & $accessor();
';

        if (! $publicProperties->isEmpty()) {
            $callParent = str_replace("\n", "\n    ", $callParent);

            $callParent = sprintf('
if (isset(self::$%s[$name])) {
    $returnValue = ($this->%s->$name = $value);
} else {
    %s
}

', $publicProperties->getName(), $valueHolderName, $callParent);
        }

        $body = "switch(\$name) {\n";
        foreach ($proxyBuilder->properties->getAccessibleProperties() as $reflectionProperty) {
            $propertyName = $reflectionProperty->getName();
            $interceptors = $proxyBuilder->getPropertyInterceptors($propertyName);
            if (count($interceptors) === 0) {
                continue;
            }

            $interceptorCode = array_map(
                static fn ($code) => str_replace("\n", "\n            ", sprintf('
$returnValue = (function () use (&$value) {
%s
})();

%s
', $code, self::INTERCEPTOR_SET_RETURN)),
                array_map(
                    static fn (Interceptor $interceptor) => str_replace("\n", "\n    ", $interceptor->getCode()),
                    $interceptors
                )
            );

            $body .= sprintf("    case '%s': \n", $propertyName);
            $body .= '        ' . implode('', $interceptorCode) . ";\n        break;\n\n";
        }

        $body .= "}\n\n";

        $body .= $callParent . "\n";
        $body .= 'return $returnValue;';

        $this->setBody($body);
    }
}
