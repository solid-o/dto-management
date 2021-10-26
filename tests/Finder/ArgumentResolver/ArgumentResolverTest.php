<?php declare(strict_types=1);

namespace Solido\DtoManagement\Tests\Finder\ArgumentResolver;

use Solido\DtoManagement\Exception\RuntimeException;
use Solido\DtoManagement\Finder\ArgumentResolver\Argument;
use Solido\DtoManagement\Finder\ArgumentResolver\ArgumentResolver;
use PHPUnit\Framework\TestCase;
use Solido\DtoManagement\Finder\ArgumentResolver\ArgumentValueResolverInterface;
use stdClass;

class ArgumentResolverTest extends TestCase
{
    private ArgumentResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArgumentResolver([new class() implements ArgumentValueResolverInterface {
            public function supports(Argument $argument): bool
            {
                return true;
            }

            public function resolve(Argument $argument): iterable
            {
                yield $argument;
            }
        }]);
    }

    public function testShouldResolveConstructorArgumentCorrectly(): void
    {
        /** @var Argument[] $args */
        $args = $this->resolver->getArguments(TestResolvableClass::class, '__construct');

        self::assertEquals(TestResolvableClass::class, $args[0]->getClassName());
        self::assertEquals('self', $args[0]->getParameterType()->getName());
        self::assertEquals('self', $args[0]->getParameterName());
        self::assertFalse($args[0]->isVariadic());
        self::assertFalse($args[0]->allowsNull());
        self::assertFalse($args[0]->hasDefault());

        self::assertEquals(TestResolvableClass::class, $args[1]->getClassName());
        self::assertEquals(stdClass::class, $args[1]->getParameterType()->getName());
        self::assertEquals('class', $args[1]->getParameterName());
        self::assertFalse($args[1]->isVariadic());
        self::assertFalse($args[1]->allowsNull());
        self::assertFalse($args[1]->hasDefault());

        self::assertEquals(TestResolvableClass::class, $args[2]->getClassName());
        self::assertEquals('string', $args[2]->getParameterType()->getName());
        self::assertEquals('scalar', $args[2]->getParameterName());
        self::assertFalse($args[2]->isVariadic());
        self::assertTrue($args[2]->allowsNull());
        self::assertFalse($args[2]->hasDefault());

        self::assertEquals(TestResolvableClass::class, $args[3]->getClassName());
        self::assertEquals('int', $args[3]->getParameterType()->getName());
        self::assertEquals('int', $args[3]->getParameterName());
        self::assertFalse($args[3]->isVariadic());
        self::assertFalse($args[3]->allowsNull());
        self::assertTrue($args[3]->hasDefault());

        self::assertEquals(TestResolvableClass::class, $args[4]->getClassName());
        self::assertNull($args[4]->getParameterType());
        self::assertEquals('others', $args[4]->getParameterName());
        self::assertTrue($args[4]->isVariadic());
        self::assertTrue($args[4]->allowsNull());
        self::assertFalse($args[4]->hasDefault());
    }

    public function testShouldNotThrowIfMethodDoesNotHaveArguments(): void
    {
        /** @var Argument[] $args */
        $args = $this->resolver->getArguments(TestResolvableClass::class, 'otherMethod');

        self::assertEmpty($args);
    }

    public function testShouldThrowIfResolverSupportsArgumentButDoesNotYieldAnyValue(): void
    {
        $this->resolver = new ArgumentResolver([new class() implements ArgumentValueResolverInterface {
            public function supports(Argument $argument): bool
            {
                return true;
            }

            public function resolve(Argument $argument): iterable
            {
                return [];
            }
        }]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('::resolve()" must yield at least one value.');

        $args = $this->resolver->getArguments(TestResolvableClass::class, '__construct');
    }
}

class TestResolvableClass
{
    public function __construct(self $self, stdClass $class, ?string $scalar, int $int = 3, ...$others)
    {
    }

    public function otherMethod(): void
    {
    }
}
