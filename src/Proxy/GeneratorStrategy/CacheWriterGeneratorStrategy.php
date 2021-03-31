<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Proxy\GeneratorStrategy;

use Closure;
use Laminas\Code\Generator\ClassGenerator;
use ProxyManager\Configuration;
use ProxyManager\GeneratorStrategy\GeneratorStrategyInterface;

use function restore_error_handler;
use function Safe\file_put_contents;
use function set_error_handler;
use function str_replace;
use function trim;

use const DIRECTORY_SEPARATOR;

class CacheWriterGeneratorStrategy implements GeneratorStrategyInterface
{
    private Configuration $configuration;
    private Closure $emptyErrorHandler;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->emptyErrorHandler = static function (): void {
            // Explicitly empty
        };
    }

    public function generate(ClassGenerator $classGenerator): string
    {
        $namespace = trim($classGenerator->getNamespaceName() ?? '', '\\');
        $namespace = $namespace ? $namespace . '\\' : $namespace;

        $className = $namespace . trim($classGenerator->getName(), '\\');
        $fileName = $this->configuration->getProxiesTargetDir() . DIRECTORY_SEPARATOR . str_replace('\\', '', $className) . '.php';

        $code = '<?php ' . $classGenerator->generate();
        file_put_contents($fileName, $code);

        set_error_handler($this->emptyErrorHandler);
        try {
            require $fileName;
        } finally {
            restore_error_handler();
        }

        return $code;
    }
}
