<?php

declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Exception\EmptyBuilderException;
use Solido\DtoManagement\Exception\ServiceNotFoundException;

class ServiceNotFoundExceptionTest extends TestCase
{
    public function testShouldHaveCode0(): void
    {
        $ex = new ServiceNotFoundException('service-id');
        self::assertEquals(0, $ex->getCode());
    }

    public function testShouldExposeTheMissingService(): void
    {
        $ex = new ServiceNotFoundException('service-id');
        self::assertEquals('You have requested a non-existent service "service-id".', $ex->getMessage());
    }

    public function testShouldExposeTheRequestingService(): void
    {
        $ex = new ServiceNotFoundException('service-id', 'requesting');
        self::assertEquals('The service "requesting" has a dependency on a non-existent service "service-id".', $ex->getMessage());
    }

    public function testShouldShowAlternatives(): void
    {
        $ex = new ServiceNotFoundException('service-id', null, null, ['alt-1', 'alt-2']);
        self::assertEquals('You have requested a non-existent service "service-id". Did you mean one of these: "alt-1", "alt-2"?', $ex->getMessage());
    }

    public function testShouldShowTheOnlyAlternative(): void
    {
        $ex = new ServiceNotFoundException('service-id', null, null, ['alt-1']);
        self::assertEquals('You have requested a non-existent service "service-id". Did you mean this: "alt-1"?', $ex->getMessage());
    }
}
