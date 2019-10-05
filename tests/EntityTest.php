<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Orm\Exception;
use Parable\Orm\Tests\Classes\TestEntity;
use Parable\Orm\Tests\Classes\TestEntityWithMissingSetters;

class EntityTest extends \PHPUnit\Framework\TestCase
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

        $entity = TestEntity::fromDatabaseItem('id', [
            'id' => 123,
            'name' => 'User McReady',
            'created_at' => $createdAt,
        ]);

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
            "Could not set primary key 'id' on Entity Parable\Orm\Tests\Classes\TestEntity from values"
        );

        TestEntity::fromDatabaseItem('id', []);
    }

    public function testFromDatabaseItemBreaksIfInvalidValuePassed(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
        );

        TestEntity::fromDatabaseItem('id', ['id' => 123, 'bloop' => 'what']);
    }

    public function testFromDatabaseItemBreaksOnMissingSetters(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Setter method 'setName' not defined on Entity Parable\Orm\Tests\Classes\TestEntityWithMissingSetters"
        );

        TestEntityWithMissingSetters::fromDatabaseItem('id', [
            'id' => 123,
            'name' => 'User McReady',
        ]);
    }

    public function testValidatePrivateKeyDoesNothingForValidKey(): void
    {
        $entity = new TestEntity();

        // This would throw if incorrect
        $entity->validatePrimaryKey('id');

        self::expectNotToPerformAssertions();
    }

    public function testValidatePrivateKeyThrowsOnInvalidKey(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Primary key property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
        );

        $entity = new TestEntity();

        $entity->validatePrimaryKey('bloop');
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
            "Primary key property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
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

    public function testGetPropertiesReturnsAllProperties(): void
    {
        $entity = new TestEntity();

        self::assertSame(
            ['id', 'name', 'created_at', 'updated_at'],
            $entity->getProperties()
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
}
