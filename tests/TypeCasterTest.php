<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Orm\Database;
use Parable\Orm\TypeCaster;

class TypeCasterTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Make sure the type caster is enabled by default
        TypeCaster::enable();
    }

    public function testEnabledTypeCasterCasts(): void
    {
        TypeCaster::enable();

        self::assertIsInt(TypeCaster::castStringToType('1'));
    }

    public function testDisabledTypeCasterCasts(): void
    {
        TypeCaster::disable();

        self::assertIsString(TypeCaster::castStringToType('1'));
    }

    public function testIntegersAreCorrectlyInterpreted(): void
    {
        self::assertTrue(TypeCaster::isInteger('1'));

        // And now things that are not integers
        self::assertFalse(TypeCaster::isInteger('0b101'));
        self::assertFalse(TypeCaster::isInteger('0756'));
        self::assertFalse(TypeCaster::isInteger('1.0'));
        self::assertFalse(TypeCaster::isInteger('totally a string'));
        self::assertFalse(TypeCaster::isInteger('{"json":true}'));
        self::assertFalse(TypeCaster::isInteger('2019-01-01'));
        self::assertFalse(TypeCaster::isInteger('12:00:00'));
        self::assertFalse(TypeCaster::isInteger('2019-01-01 12:00:00'));

        self::assertSame(1, TypeCaster::castStringToType('1'));

        // And these need to stay strings
        self::assertSame('0756', TypeCaster::castStringToType('0756')); // binary
        self::assertSame('0b101', TypeCaster::castStringToType('0b101')); // octal
    }

    public function testFloatsAreCorrectlyInterpreted(): void
    {
        self::assertTrue(TypeCaster::isFloat('1.0'));

        // And now things that are not floats
        self::assertFalse(TypeCaster::isFloat('1'));
        self::assertFalse(TypeCaster::isFloat('0b101')); // binary
        self::assertFalse(TypeCaster::isFloat('0756')); // octal
        self::assertFalse(TypeCaster::isFloat('totally a string'));
        self::assertFalse(TypeCaster::isFloat('{"json":true}'));
        self::assertFalse(TypeCaster::isFloat('2019-01-01'));
        self::assertFalse(TypeCaster::isFloat('12:00:00'));
        self::assertFalse(TypeCaster::isFloat('2019-01-01 12:00:00'));

        self::assertSame('1.0', TypeCaster::castStringToType('1.0'));
    }

    public function testDatesAreCorrectlyInterpreted(): void
    {
        self::assertTrue(TypeCaster::isDate('2019-01-01'));

        // And now things that are not dates
        self::assertFalse(TypeCaster::isDate('1'));
        self::assertFalse(TypeCaster::isDate('0b101')); // binary
        self::assertFalse(TypeCaster::isDate('0756')); // octal
        self::assertFalse(TypeCaster::isDate('1.0'));
        self::assertFalse(TypeCaster::isDate('totally a string'));
        self::assertFalse(TypeCaster::isDate('{"json":true}'));
        self::assertFalse(TypeCaster::isDate('12:00:00'));
        self::assertFalse(TypeCaster::isDate('2019-01-01 12:00:00'));

        $cast_value = TypeCaster::castStringToType('2019-01-01');

        self::assertInstanceOf(DateTimeImmutable::class, $cast_value);
        self::assertSame('2019-01-01', $cast_value->format(Database::DATE_SQL));
    }

    public function testTimesAreCorrectlyInterpreted(): void
    {
        self::assertTrue(TypeCaster::isTime('12:00:00'));

        // And now things that are not times
        self::assertFalse(TypeCaster::isTime('1'));
        self::assertFalse(TypeCaster::isTime('0b101')); // binary
        self::assertFalse(TypeCaster::isTime('0756')); // octal
        self::assertFalse(TypeCaster::isTime('1.0'));
        self::assertFalse(TypeCaster::isTime('totally a string'));
        self::assertFalse(TypeCaster::isTime('{"json":true}'));
        self::assertFalse(TypeCaster::isTime('2019-01-01'));
        self::assertFalse(TypeCaster::isTime('2019-01-01 12:00:00'));

        $cast_value = TypeCaster::castStringToType('12:00:00');

        self::assertInstanceOf(DateTimeImmutable::class, $cast_value);
        self::assertSame('12:00:00', $cast_value->format(Database::TIME_SQL));
    }

    public function testDateTimesAreCorrectlyInterpreted(): void
    {
        self::assertTrue(TypeCaster::isDateTime('2019-01-01 12:00:00'));

        // And now things that are not datetimes
        self::assertFalse(TypeCaster::isDateTime('1'));
        self::assertFalse(TypeCaster::isDateTime('0b101')); // binary
        self::assertFalse(TypeCaster::isDateTime('0756')); // octal
        self::assertFalse(TypeCaster::isDateTime('1.0'));
        self::assertFalse(TypeCaster::isDateTime('totally a string'));
        self::assertFalse(TypeCaster::isDateTime('{"json":true}'));
        self::assertFalse(TypeCaster::isDateTime('2019-01-01'));
        self::assertFalse(TypeCaster::isDateTime('12:00:00'));

        $cast_value = TypeCaster::castStringToType('2019-01-01 12:00:00');

        self::assertInstanceOf(DateTimeImmutable::class, $cast_value);
        self::assertSame('2019-01-01 12:00:00', $cast_value->format(Database::DATETIME_SQL));
    }

    public function testEverythingUnknownRemainsAsString(): void
    {
        self::assertTrue(is_string(TypeCaster::castStringToType('0b101')));
        self::assertTrue(is_string(TypeCaster::castStringToType('0756')));
        self::assertTrue(is_string(TypeCaster::castStringToType('1.0')));
        self::assertTrue(is_string(TypeCaster::castStringToType('totally a string')));
        self::assertTrue(is_string(TypeCaster::castStringToType('{"json":true}')));

        // And now things that are not going to remain strings
        self::assertFalse(is_string(TypeCaster::castStringToType('1')));
        self::assertFalse(is_string(TypeCaster::castStringToType('2019-01-01 12:00:00')));
        self::assertFalse(is_string(TypeCaster::castStringToType('2019-01-01')));
        self::assertFalse(is_string(TypeCaster::castStringToType('12:00:00')));
    }
}