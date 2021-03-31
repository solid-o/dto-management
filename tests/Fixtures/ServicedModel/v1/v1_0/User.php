<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Fixtures\ServicedModel\v1\v1_0;

use Solido\DtoManagement\Tests\Fixtures\DefinedService;
use Solido\DtoManagement\Tests\Fixtures\ServicedModel\Interfaces\UserInterface;

class User implements UserInterface
{
    public \stdClass $service1;
    public DefinedService $service;

    public function __construct(\stdClass $service1, DefinedService $service)
    {
        $this->service1 = $service1;
        $this->service = $service;
    }
}
