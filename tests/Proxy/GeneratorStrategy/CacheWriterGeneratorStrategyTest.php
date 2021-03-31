<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Proxy\GeneratorStrategy;

use Laminas\Code\Generator\MethodGenerator;
use Solido\DtoManagement\Proxy\Builder\ProxyBuilder;
use Solido\DtoManagement\Proxy\Extension\ExtensionInterface;
use Solido\DtoManagement\Proxy\Factory\AccessInterceptorFactory;
use Solido\DtoManagement\Proxy\Factory\Configuration;
use Solido\DtoManagement\Proxy\GeneratorStrategy\CacheWriterGeneratorStrategy;
use PHPUnit\Framework\TestCase;

class CacheWriterGeneratorStrategyTest extends TestCase
{
    public function testShouldWriteProxyGeneratedClassInCacheDir(): void
    {
        $dir = tempnam(sys_get_temp_dir(), 'dto-mgmt-test');
        unlink($dir);
        mkdir($dir);

        $configuration = new Configuration();
        $configuration->setProxiesTargetDir($dir);
        $configuration->addExtension(new class implements ExtensionInterface {
            public function extend(ProxyBuilder $proxyBuilder): void
            {
                $proxyBuilder->addMethod(new MethodGenerator('fooBar'));
            }
        });

        $configuration->setGeneratorStrategy(new CacheWriterGeneratorStrategy($configuration));

        $generator = new AccessInterceptorFactory($configuration);
        $className = $generator->generateProxy(self::class);

        self::assertStringMatchesFormat(<<<'FILE'
<?php namespace ProxyManagerGeneratedProxy\__PM__\Solido\DtoManagement\Tests\Proxy\GeneratorStrategy\CacheWriterGeneratorStrategyTest;

use Solido\DtoManagement\Proxy\Interceptor\ReturnValue;

class Generated%a extends \Solido\DtoManagement\Tests\Proxy\GeneratorStrategy\CacheWriterGeneratorStrategyTest implements \Solido\DtoManagement\Proxy\ProxyInterface
{
    %a

    public function fooBar()
    {
    }
%a
FILE
, file_get_contents($dir . '/' . str_replace('\\', '', $className) . '.php'));
    }
}
