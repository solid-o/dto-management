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

use const DIRECTORY_SEPARATOR;

class CacheWriterGeneratorStrategy implements GeneratorStrategyInterface
{
    private Closure $emptyErrorHandler;

    public function __construct(private Configuration $configuration)
    {
        $this->emptyErrorHandler = static function (): void {
            // Explicitly empty
        };
    }

    public function generate(ClassGenerator $classGenerator): string
    {
        $className = ($classGenerator->getNamespaceName() ?? '') . $classGenerator->getName();
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
