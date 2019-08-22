<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Orm\Exception;
use Parable\Orm\Tests\Classes\TestEntity;
use Parable\Orm\Tests\Classes\TestEntityWithMissingSetters;
use Parable\Orm\Tests\Classes\TestEntityWithoutTraits;

class EntityTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateValidEntityAndToArray()
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

    public function testGetPrimaryKey()
    {
        $entity = new TestEntity();

        self::assertNull($entity->getPrimaryKey('id'));

        $entity = new TestEntity(123);

        self::assertSame(123, $entity->getPrimaryKey('id'));
    }

    public function testFromDatabaseItem()
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

    public function testFromDatabaseItemBreaksIfIdOmitted()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Could not set primary key 'id' on Entity Parable\Orm\Tests\Classes\TestEntity from values"
        );

        TestEntity::fromDatabaseItem('id', []);
    }

    public function testFromDatabaseItemBreaksIfInvalidValuePassed()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
        );

        TestEntity::fromDatabaseItem('id', ['id' => 123, 'bloop' => 'what']);
    }

    public function testFromDatabaseItemBreaksOnMissingSetters()
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

    public function testValidatePrivateKeyDoesNothingForValidKey()
    {
        $entity = new TestEntity();

        // This would throw if incorrect
        $entity->validatePrimaryKey('id');

        self::expectNotToPerformAssertions();
    }

    public function testValidatePrivateKeyThrowsOnInvalidKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Primary key property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
        );

        $entity = new TestEntity();

        $entity->validatePrimaryKey('bloop');
    }

    public function testGetPrimaryKeyWorks()
    {
        $entity = new TestEntity(4321);

        self::assertSame(4321, $entity->getPrimaryKey('id'));
    }

    public function testGetPrimaryKeyThrowsOnInvalidKey()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Primary key property 'bloop' does not exist on Entity Parable\Orm\Tests\Classes\TestEntity"
        );

        $entity = new TestEntity();

        self::assertNull($entity->getPrimaryKey('bloop'));
    }

    public function testCreateValidEntityAndToArrayWithoutEmptyValues()
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

    public function testCreateValidEntityAndToArrayWithOnlyChanges()
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

    public function testGetPropertiesReturnsAllProperties()
    {
        $entity = new TestEntity();

        self::assertSame(
            ['id', 'name', 'created_at', 'updated_at'],
            $entity->getProperties()
        );
    }

    public function testMarkCreatedAt()
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

    public function testMarkUpdatedAt()
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
}
