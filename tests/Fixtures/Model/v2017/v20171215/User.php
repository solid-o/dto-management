<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures\Model\v2017\v20171215;

use Solido\DtoManagement\Tests\Fixtures\Model\Interfaces\UserInterface;

class User implements UserInterface
{
    public $barPublic = 'pubb';
    public $barBar = 'test';
    public $foobar = 'ciao';

    public function __construct()
    {
    }

    public function setFoo(?string $value)
    {
        $this->foo = $value;
    }

    public function getFoo()
    {
        return 'test';
    }

    public function setBar()
    {
        $this->foobar = 'testtest';
    }

    public function fluent(): self
    {
        return $this;
    }

    public function getTest(): ?string
    {
        return 'unavailable_test';
    }
}
