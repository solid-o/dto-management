<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Finder;

use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Exception\RuntimeException;
use Solido\DtoManagement\Exception\ServiceCircularReferenceException;
use Solido\DtoManagement\Exception\ServiceNotFoundException;
use Solido\DtoManagement\Finder\ServiceLocator;
use Solido\DtoManagement\Finder\ServiceLocatorRegistry;
use Solido\DtoManagement\Tests\Fixtures;

class ServiceLocatorTest extends TestCase
{
    public function testHasShouldWork(): void
    {
        $locator = new ServiceLocator(Fixtures\SemVerModel\Interfaces\UserInterface::class, [
            '1.0' => fn () => new Fixtures\SemVerModel\v1\v1_0\User(),
            '1.1' => fn () => new Fixtures\SemVerModel\v1\v1_1\User(),
        ]);

        self::assertFalse($locator->has('0.9'));
        self::assertFalse($locator->has('1.0-alpha.1'));
        self::assertTrue($locator->has('1.0'));
        self::assertTrue($locator->has('2.0'));
    }

    public function testHasShouldWorkWithIntegerVersions(): void
    {
        $locator = new ServiceLocator(Fixtures\SemVerModel\Interfaces\UserInterface::class, [
            20210316 => fn () => new Fixtures\SemVerModel\v1\v1_0\User(),
            20210318 => fn () => new Fixtures\SemVerModel\v1\v1_1\User(),
        ]);

        self::assertFalse($locator->has(20202001));
        self::assertTrue($locator->has(20210316));
        self::assertTrue($locator->has(20210317));
        self::assertTrue($locator->has(20210318));

        self::assertNotNull($locator->get(20210318));
    }

    public function testGetLatestShouldReturnTheLatestVersion(): void
    {
        $locator = new ServiceLocator(Fixtures\SemVerModel\Interfaces\UserInterface::class, [
            '2.0' => fn () => new Fixtures\SemVerModel\v2\v2_0_alpha_1\User(),
            '1.1' => fn () => new Fixtures\SemVerModel\v1\v1_1\User(),
            '1.0' => fn () => new Fixtures\SemVerModel\v1\v1_0\User(),
        ]);

        self::assertInstanceOf(
            Fixtures\SemVerModel\v2\v2_0_alpha_1\User::class,
            $locator->get('latest')
        );
    }

    public function testGetShouldGetTheCorrectDtoVersion(): void
    {
        $locator = new ServiceLocator(Fixtures\SemVerModel\Interfaces\UserInterface::class, [
            '1.0' => fn () => new Fixtures\SemVerModel\v1\v1_0\User(),
            '1.1' => fn () => new Fixtures\SemVerModel\v1\v1_1\User(),
            '2.0' => fn () => new Fixtures\SemVerModel\v2\v2_0_alpha_1\User(),
        ]);

        self::assertInstanceOf(Fixtures\SemVerModel\v1\v1_1\User::class, $locator->get('1.2'));
        self::assertInstanceOf(Fixtures\SemVerModel\v1\v1_1\User::class, $locator('1.2'));
        self::assertNull($locator('0.2'));
    }

    public function testGetShouldThrowOnCircularDependency(): void
    {
        $this->expectException(ServiceCircularReferenceException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Circular reference detected for service "0.1", path: "0.1 -> 0.1".');

        $locator = new ServiceLocator(Fixtures\Model\Interfaces\UserInterface::class, [
            '0.1' => static function () use (&$locator) {
                return new Fixtures\Model\v2017\v20171215\Circular($locator);
            },
        ]);

        try {
            $locator->get('0.1');
        } catch (ServiceCircularReferenceException $e) {
            self::assertEquals('0.1', $e->getServiceId());
            self::assertEquals(['0.1', '0.1'], $e->getPath());
            throw $e;
        }
    }

    public function testGetShouldThrowOnInvalidVersion(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectDeprecationMessage('You have requested a non-existent version "0.1" for service "Solido\DtoManagement\Tests\Fixtures\Model\Interfaces\UserInterface".');

        $locator = new ServiceLocator(Fixtures\Model\Interfaces\UserInterface::class, [
            '1.0' => static function () use (&$locator) {
                return new Fixtures\Model\v2017\v20171215\Circular($locator);
            },
        ]);

        try {
            $locator->get('0.1');
        } catch (ServiceNotFoundException $e) {
            self::assertEquals(Fixtures\Model\Interfaces\UserInterface::class, $e->getId());
            self::assertEquals('0.1', $e->getVersion());
            self::assertNull($e->getSourceId());
            self::assertEquals(['1.0'], $e->getAlternatives());
            throw $e;
        }
    }

    public function testGetShouldThrowOnNonResolvableService(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('The version "1.0" has a dependency on a non-existent version "0.1" for service "Solido\DtoManagement\Tests\Fixtures\Model\Interfaces\UserInterface". Did you mean this: "1.0"?');

        $locator = new ServiceLocator(Fixtures\Model\Interfaces\UserInterface::class, [
            '1.0' => static function () use (&$locator) {
                return new Fixtures\Model\v2017\v20171215\Circular($locator);
            },
        ]);

        $locator->get('1.0');
    }
}
