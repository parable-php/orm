<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Orm\AbstractEntity;
use Parable\Orm\Exception;
use Parable\Orm\Features\HasTypedProperties;
use Parable\Orm\PropertyTypes\BooleanPropertyTyper;
use Parable\Orm\PropertyTypes\DatePropertyTyper;
use Parable\Orm\PropertyTypes\DateTimePropertyTyper;
use Parable\Orm\PropertyTypes\IntegerPropertyTyper;
use Parable\Orm\PropertyTypes\PropertyTypeDeterminer;
use Parable\Orm\PropertyTypes\TimePropertyTyper;
use Parable\Orm\Tests\Classes\CustomPropertyTyper;
use PHPUnit\Framework\TestCase;

class PropertyTypeDeterminerTest extends TestCase
{
    public function testIntegerTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(IntegerPropertyTyper::class);

        $typed = PropertyTypeDeterminer::typeProperty($entity, 'unused', '1');

        self::assertSame(1, $typed);

        $untyped = PropertyTypeDeterminer::untypeProperty($entity, 'unused', 1);

        self::assertSame('1', $untyped);
    }

    public function testIntegerTyperThrowsOnTypingInvalidValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'blabla' as integer");

        $entity = $this->createEntityWithSpecificTyper(IntegerPropertyTyper::class);

        PropertyTypeDeterminer::typeProperty($entity, 'unused', 'blabla');
    }

    public function testIntegerTyperThrowsOnUntypingInvalidValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not untype 'blabla' from integer");

        $entity = $this->createEntityWithSpecificTyper(IntegerPropertyTyper::class);

        PropertyTypeDeterminer::untypeProperty($entity, 'unused', 'blabla');
    }

    public function testBooleanTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(BooleanPropertyTyper::class);

        $typedTrue = PropertyTypeDeterminer::typeProperty($entity, 'unused', '1');
        $typedFalse = PropertyTypeDeterminer::typeProperty($entity, 'unused', '0');

        self::assertTrue($typedTrue);
        self::assertFalse($typedFalse);

        $untypedTrue = PropertyTypeDeterminer::untypeProperty($entity, 'unused', true);
        $untypedFalse = PropertyTypeDeterminer::untypeProperty($entity, 'unused', false);

        self::assertSame('1', $untypedTrue);
        self::assertSame('0', $untypedFalse);
    }

    public function testBooleanTyperThrowsOnTypingInvalidValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'blabla' as boolean");

        $entity = $this->createEntityWithSpecificTyper(BooleanPropertyTyper::class);

        PropertyTypeDeterminer::typeProperty($entity, 'unused', 'blabla');
    }

    public function testBooleanTyperThrowsOnUntypingInvalidValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not untype 'blabla' from boolean");

        $entity = $this->createEntityWithSpecificTyper(BooleanPropertyTyper::class);

        PropertyTypeDeterminer::untypeProperty($entity, 'unused', 'blabla');
    }

    public function testDateTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(DatePropertyTyper::class);

        $typed = PropertyTypeDeterminer::typeProperty($entity, 'unused', '2020-05-25');

        self::assertInstanceOf(DateTimeImmutable::class, $typed);

        $untyped = PropertyTypeDeterminer::untypeProperty($entity, 'unused', $typed);

        self::assertSame('2020-05-25', $untyped);
    }

    public function testTimeTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(TimePropertyTyper::class);

        $typed = PropertyTypeDeterminer::typeProperty($entity, 'unused', '12:34:56');

        self::assertInstanceOf(DateTimeImmutable::class, $typed);

        $untyped = PropertyTypeDeterminer::untypeProperty($entity, 'unused', $typed);

        self::assertSame('12:34:56', $untyped);
    }

    public function testDateTimeTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(DateTimePropertyTyper::class);

        $typed = PropertyTypeDeterminer::typeProperty($entity, 'unused', '2020-05-25 12:34:56');

        self::assertInstanceOf(DateTimeImmutable::class, $typed);

        $untyped = PropertyTypeDeterminer::untypeProperty($entity, 'unused', $typed);

        self::assertSame('2020-05-25 12:34:56', $untyped);
    }

    public function testCustomTyperWorksAsExpected(): void
    {
        $entity = $this->createEntityWithSpecificTyper(CustomPropertyTyper::class);

        $typed = PropertyTypeDeterminer::typeProperty($entity, 'unused', 'test');

        self::assertSame('CUSTOMIZED/test', $typed);

        $untyped = PropertyTypeDeterminer::untypeProperty($entity, 'unused', $typed);

        self::assertSame('test', $untyped);
    }

    private function createEntityWithSpecificTyper(string $typerClass): AbstractEntity
    {
        return new class ($typerClass) extends AbstractEntity implements HasTypedProperties {
            private string $typerClass;

            public function __construct(string $typerClass)
            {
                $this->typerClass = $typerClass;
            }

            public function getPropertyType(string $property): ?string
            {
                return $this->typerClass;
            }
        };
    }
}
