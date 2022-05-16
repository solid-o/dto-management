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
        $ex = new ServiceNotFoundException('service-id', '1.0');
        self::assertEquals(0, $ex->getCode());
    }

    public function testShouldExposeTheMissingService(): void
    {
        $ex = new ServiceNotFoundException('service-id', '');
        self::assertEquals('You have requested a non-existent version "latest" for service "service-id".', $ex->getMessage());
    }

    public function testShouldExposeTheRequestingService(): void
    {
        $ex = new ServiceNotFoundException('service-id', '1.0', 'requesting');
        self::assertEquals('The version "requesting" has a dependency on a non-existent version "1.0" for service "service-id".', $ex->getMessage());
    }

    public function testShouldShowAlternatives(): void
    {
        $ex = new ServiceNotFoundException('service-id', '1.0', null, null, ['alt-1', 'alt-2']);
        self::assertEquals('You have requested a non-existent version "1.0" for service "service-id". Did you mean one of these: "alt-1", "alt-2"?', $ex->getMessage());
    }

    public function testShouldShowTheOnlyAlternative(): void
    {
        $ex = new ServiceNotFoundException('service-id', '1.0', null, null, ['alt-1']);
        self::assertEquals('You have requested a non-existent version "1.0" for service "service-id". Did you mean this: "alt-1"?', $ex->getMessage());
    }
}
