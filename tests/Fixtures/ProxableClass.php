<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures;

class ProxableClass
{
    public $publicProperty;
    protected $protectedProperty;
    private $privateProperty;

    public function publicMethod(): void
    {
    }

    protected function protectedMethod(): void
    {
    }

    private function privateMethod(): void
    {
    }

    final public function finalMethod(): void
    {
    }
}
