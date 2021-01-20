<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Di\Container;
use Parable\Orm\Database;
use Parable\Orm\Exception;
use Parable\Orm\PropertyTypes\PropertyTypeDeterminer;
use Parable\Orm\Tests\Classes\TestEntity;
use Parable\Orm\Tests\Classes\TestEntityWithMissingSetters;
use Parable\Orm\Tests\Classes\TestEntityWithTypedProperties;
use PHPUnit\Framework\TestCase;

class EntityTest extends TestCase
{
    public function testCreateValidEntityAndToArray(): void
    {
        $entity = new TestEntity();

        $entity->setName('User McReady');

        self::assertSame(
            [
                'id' => null,
                'name' => 'User McReady',
                'created_at' => null,
                'updated_at' => null,
            ],
            $entity->toArray()
        );
    }

    public function testGetPrimaryKey(): void
    {
        $entity = new TestEntity();

        self::assertNull($entity->getPrimaryKey('id'));

        $entity = new TestEntity(123);

        self::assertSame(123, $entity->getPrimaryKey('id'));
    }

    public function testFromDatabaseItem(): void
    {
        $createdAt = date('Y-m-d H:i:s');

        $entity = TestEntity::fromDatabaseItem(
            new Container(),
            'id',
            [
                'id' => 123,
                'name' => 'User McReady',
                'created_at' => $createdAt,
            ]
        );

        self::assertSame(
            [
                'id' => 123,
                'name' => 'User McReady',
                'created_at' => $createdAt,
                'updated_at' => null,
            ],
            $entity->toArray()
        );
    }

    public function testFromDatabaseItemBreaksIfIdOmitted(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Could not set primary key 'id' on entity Parable\Orm\Tests\Classes\TestEntity from database values"
        );

        TestEntity::fromDatabaseItem(new Container(), 'id', []);
    }

    public function testFromDatabaseItemBreaksIfInvalidValuePassed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Property 'bloop' does not exist on entity Parable\Orm\Tests\Classes\TestEntity"
        );

        TestEntity::fromDatabaseItem(new Container(), 'id', ['id' => '123', 'bloop' => 'what']);
    }

    public function testFromDatabaseItemBreaksOnMissingSetters(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Setter method 'setName' not defined on entity Parable\Orm\Tests\Classes\TestEntityWithMissingSetters"
        );

        TestEntityWithMissingSetters::fromDatabaseItem(
            new Container(),
            'id',
            [
                'id' => 123,
                'name' => 'User McReady',
            ]
        );
    }

    public function testGetPrimaryKeyWorks(): void
    {
        $entity = new TestEntity(4321);

        self::assertSame(4321, $entity->getPrimaryKey('id'));
    }

    public function testGetPrimaryKeyThrowsOnInvalidKey(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Primary key property 'bloop' does not exist on entity Parable\Orm\Tests\Classes\TestEntity"
        );

        $entity = new TestEntity();

        self::assertNull($entity->getPrimaryKey('bloop'));
    }

    public function testToArrayWithout(): void
    {
        $entity = new TestEntity();

        $entity->setName('User McReady');

        self::assertSame(
            [
                'id' => null,
                'name' => 'User McReady',
                'created_at' => null,
                'updated_at' => null,
            ],
            $entity->toArray()
        );

        self::assertSame(
            [
                'id' => null,
                'updated_at' => null,
            ],
            $entity->toArrayWithout('name', 'created_at')
        );
    }

    public function testToArrayWithoutSees0AsNonEmpty(): void
    {
        $entity = new TestEntity();

        $entity->setName('0');

        self::assertSame(
            [
                'name' => '0',
            ],
            $entity->toArrayWithoutEmptyValues()
        );
    }

    public function testCreateValidEntityAndToArrayWithoutEmptyValues(): void
    {
        $entity = new TestEntity();

        $entity->setName('User McReady');

        self::assertSame(
            [
                'name' => 'User McReady',
            ],
            $entity->toArrayWithoutEmptyValues()
        );
    }

    public function testCreateValidEntityAndToArrayWithOnlyChanges(): void
    {
        $entity = new TestEntity(1, 'User Name');

        self::assertSame(
            [
                'id' => 1,
                'name' => 'User Name',
                'created_at' => null,
                'updated_at' => null,
            ],
            $entity->toArray()
        );

        // No changes, so it should be empty
        self::assertSame(
            [],
            $entity->toArrayWithOnlyChanges()
        );

        $entity->setName('User McReady');

        // Now changes, so it should be empty
        self::assertSame(
            [
                'name' => 'User McReady',
            ],
            $entity->toArrayWithOnlyChanges()
        );
    }

    public function testMarkCreatedAt(): void
    {
        $entity = new TestEntity();

        self::assertNull($entity->getCreatedAt());

        $entity->markCreatedAt();

        self::assertNotNull($entity->getCreatedAt());

        self::assertInstanceOf(
            DateTimeImmutable::class,
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $entity->getCreatedAt())
        );
    }

    public function testMarkUpdatedAt(): void
    {
        $entity = new TestEntity();

        self::assertNull($entity->getUpdatedAt());

        $entity->markUpdatedAt();

        self::assertNotNull($entity->getUpdatedAt());

        self::assertInstanceOf(
            DateTimeImmutable::class,
            DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $entity->getUpdatedAt())
        );
    }

    public function testHasBeenMarkedAsOriginal(): void
    {
        // TestEntity has been marked as original automatically.
        $entity = new TestEntity();

        self::assertTrue($entity->hasBeenMarkedAsOriginal());

        $entity->unmarkAsOriginal();

        self::assertFalse($entity->hasBeenMarkedAsOriginal());

        $entity->markAsOriginal();

        self::assertTrue($entity->hasBeenMarkedAsOriginal());
    }

    public function testTypedProperties(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $id = PropertyTypeDeterminer::typeProperty($entity, 'id', '1');
        $date = PropertyTypeDeterminer::typeProperty($entity, 'date', '2019-12-01');
        $time = PropertyTypeDeterminer::typeProperty($entity, 'time', '12:34:45');
        $datetime = PropertyTypeDeterminer::typeProperty($entity, 'datetime', '2019-12-01 12:34:45');
        $updatedAt = PropertyTypeDeterminer::typeProperty($entity, 'datetime', null);

        self::assertIsInt($id);
        self::assertInstanceOf(DateTimeImmutable::class, $date);
        self::assertInstanceOf(DateTimeImmutable::class, $time);
        self::assertInstanceOf(DateTimeImmutable::class, $datetime);
        self::assertNull($updatedAt);

        $id = PropertyTypeDeterminer::untypeProperty($entity, 'id', $id);
        $date = PropertyTypeDeterminer::untypeProperty($entity, 'date', $date);
        $time = PropertyTypeDeterminer::untypeProperty($entity, 'time', $time);
        $datetime = PropertyTypeDeterminer::untypeProperty($entity, 'datetime', $datetime);
        $updatedAt = PropertyTypeDeterminer::untypeProperty($entity, 'datetime', $updatedAt);

        self::assertSame('1', $id);
        self::assertSame('2019-12-01', $date);
        self::assertSame('12:34:45', $time);
        self::assertSame('2019-12-01 12:34:45', $datetime);
        self::assertNull($updatedAt);
    }

    public function testTypingIntThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'bla' as integer");

        PropertyTypeDeterminer::typeProperty($entity, 'id', 'bla');
    }

    public function testTypingDateTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'bla' as date with format " . Database::DATE_SQL);

        PropertyTypeDeterminer::typeProperty($entity, 'date', 'bla');
    }

    public function testTypingTimeTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'bla' as time with format " . Database::TIME_SQL);

        PropertyTypeDeterminer::typeProperty($entity, 'time', 'bla');
    }

    public function testTypingDateTimeTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not type 'bla' as datetime with format " . Database::DATETIME_SQL);

        PropertyTypeDeterminer::typeProperty($entity, 'datetime', 'bla');
    }

    public function testUntypeOnNonNumericIntThrows(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Could not untype 'bla' from integer");

        PropertyTypeDeterminer::untypeProperty($entity, 'id', 'bla');
    }

    public function testUntypingDateTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Could not untype 'bla' as date from DateTimeInterface with format " . Database::DATE_SQL
        );

        PropertyTypeDeterminer::untypeProperty($entity, 'date', 'bla');
    }

    public function testUntypingTimeTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Could not untype 'bla' as time from DateTimeInterface with format " . Database::TIME_SQL
        );

        PropertyTypeDeterminer::untypeProperty($entity, 'time', 'bla');
    }

    public function testUntypingDateTimeTypeThrowsOnInvalidValue(): void
    {
        $entity = new TestEntityWithTypedProperties();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Could not untype 'bla' as datetime from DateTimeInterface with format " . Database::DATETIME_SQL
        );

        PropertyTypeDeterminer::untypeProperty($entity, 'datetime', 'bla');
    }

    public function testUntypeOnNullDoesNothing(): void
    {
        $entity = new TestEntityWithTypedProperties();

        self::assertNull(PropertyTypeDeterminer::untypeProperty($entity, 'datetime', null));
    }
}
