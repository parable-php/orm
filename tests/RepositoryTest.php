<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use Parable\Di\Container;
use Parable\Orm\Database;
use Parable\Orm\Exception;
use Parable\Orm\Tests\Classes\TestEntity;
use Parable\Orm\Tests\Classes\TestEntityWithoutTraits;
use Parable\Orm\Tests\Classes\TestRepository;
use Parable\Orm\Tests\Classes\TestRepositoryWithoutPrimaryKey;
use Parable\Query\OrderBy;
use Parable\Query\Query;
use stdClass;

class RepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Database
     */
    protected $database;

    /**
     * @var TestRepository
     */
    protected $repository;

    /**
     * @var TestEntity
     */
    protected $defaultUser;

    public function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        $this->database = $this->container->get(Database::class);
        $this->container->store($this->database, Database::class);

        $this->database->setType(Database::TYPE_SQLITE);
        $this->database->setDatabaseName(':memory:');
        $this->database->connect();

        $this->database->query("
            CREATE TABLE users (
              id INTEGER PRIMARY KEY,
              name TEXT NOT NULL,
              created_at TEXT NOT NULL,
              updated_at TEXT DEFAULT NULL
            );
        ");

        $this->repository = $this->container->build(TestRepository::class);

        $this->defaultUser = new TestEntity();
        $this->defaultUser->setName('Default User');

        $this->repository->save($this->defaultUser);

        $this->resetQueryCount($this->database);
    }

    protected function resetQueryCount(Database $database): void
    {
        $resetter = new class extends Database {
            public function __invoke(Database $database)
            {
                $database->queryCount = 0;
            }
        };

        $resetter($database);
    }

    public function testCreateRepositorySuccessful()
    {
        self::assertInstanceOf(TestRepository::class, $this->repository);
        self::assertSame(TestEntity::class, $this->repository->getEntityClass());
        self::assertSame('users', $this->repository->getTableName());
        self::assertSame('id', $this->repository->getPrimaryKey());

        self::assertCount(1, $this->repository->findAll());
    }

    public function testCreateEntityClass()
    {
        self::assertInstanceOf(TestEntity::class, $this->repository->createEntityClass());
    }

    public function testCreateEntityClassReturnsNewOneEveryTime()
    {
        self::assertInstanceOf(TestEntity::class, $entity1 = $this->repository->createEntityClass());
        self::assertInstanceOf(TestEntity::class, $entity2 = $this->repository->createEntityClass());

        self::assertNotSame($entity1, $entity2);
    }

    public function testCreateEntityClassThrowsOnInvalidClassName()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Traits class 'noooope' does not exist.");

        $repo = new class (...$this->container->getDependenciesFor(TestRepository::class)) extends TestRepository {
            public function getEntityClass(): string {
                return 'noooope';
            }
        };

        $repo->createEntityClass();
    }

    public function testCreateEntityClassThrowsOnClassThatDoesNotExtendAbstractEntity()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Class 'stdClass' does not extend AbstractEntity.");

        $repo = new class (...$this->container->getDependenciesFor(TestRepository::class)) extends TestRepository {
            public function getEntityClass(): string {
                return stdClass::class;
            }
        };

        $repo->createEntityClass();
    }

    public function testFind()
    {
        $defaultUser = $this->repository->find(1);

        self::assertInstanceOf(TestEntity::class, $defaultUser);
        self::assertSame($this->defaultUser->toArray(), $defaultUser->toArray());
    }

    public function testFindWithInvalidKeyReturnsNull()
    {
        $defaultUser = $this->repository->find(999);

        self::assertNull($defaultUser);
    }

    public function testCountAll()
    {
        $userCount = $this->repository->countAll();

        self::assertSame(1, $userCount);

        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        $userCount = $this->repository->countAll();

        self::assertSame(4, $userCount);
    }

    public function testFindAll()
    {
        $user = new TestEntity();
        $user->setName('First Custom User');

        $this->repository->save($user);

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll();

        self::assertCount(2, $users);

        self::assertSame(
            'Default User',
            $users[0]->getName()
        );
        self::assertSame(
            'First Custom User',
            $users[1]->getName()
        );
    }

    public function testFindAllWithOrder()
    {
        $user = new TestEntity();
        $user->setName('First Custom User');

        $this->repository->save($user);

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll(OrderBy::asc('id'));

        self::assertCount(2, $users);

        self::assertSame(
            'Default User',
            $users[0]->getName()
        );
        self::assertSame(
            'First Custom User',
            $users[1]->getName()
        );

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll(OrderBy::desc('id'));

        self::assertCount(2, $users);

        self::assertSame(
            'First Custom User',
            $users[0]->getName()
        );
        self::assertSame(
            'Default User',
            $users[1]->getName()
        );
    }

    public function testFindAllWithLimit()
    {
        $user = new TestEntity();
        $user->setName('First Custom User');

        $this->repository->save($user);

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll(OrderBy::asc('id'), 1);

        self::assertCount(1, $users);

        self::assertSame(
            'Default User',
            $users[0]->getName()
        );
    }

    public function testFindAllWithLimitAndOffset()
    {
        $user1 = new TestEntity();
        $user1->setName('First Custom User');

        $user2 = new TestEntity();
        $user2->setName('Second Custom User');

        $this->repository->saveAll($user1, $user2);

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll(OrderBy::asc('id'), 1, 1);

        self::assertCount(1, $users);

        self::assertSame(
            'First Custom User',
            $users[0]->getName()
        );
    }

    public function testFindAllWithOrderDescendingLimitAndOffset()
    {
        $user1 = new TestEntity();
        $user1->setName('First Custom User');

        $user2 = new TestEntity();
        $user2->setName('Second Custom User');

        $this->repository->saveAll($user1, $user2);

        /** @var TestEntity[] $users */
        $users = $this->repository->findAll(OrderBy::desc('id'), 2, 1);

        self::assertCount(1, $users);

        self::assertSame(
            'Default User',
            $users[0]->getName()
        );
    }

    public function testFindBy()
    {
        $user = new TestEntity();
        $user->setName('First Custom User');

        $this->repository->save($user);

        /** @var TestEntity[] $users */
        $users = $this->repository->findBy(function (Query $query) {
            $query->where('name', '=', 'First Custom User');
        });

        self::assertCount(1, $users);

        self::assertSame(
            'First Custom User',
            $users[0]->getName()
        );
    }

    public function testFindByWithOrder()
    {
        $user1 = new TestEntity();
        $user1->setName('First Custom User');

        $user2 = new TestEntity();
        $user2->setName('Second Custom User');

        $this->repository->saveAll($user1, $user2);

        /** @var TestEntity[] $users */
        $users = $this->repository->findBy(function (Query $query) {
            $query->where('name', 'LIKE', '%Custom%');
        }, OrderBy::desc('id'));

        self::assertCount(2, $users);

        self::assertSame(
            'Second Custom User',
            $users[0]->getName()
        );
    }

    public function testFindUniqueByReturnsNullIfNoneFound()
    {
        /** @var TestEntity[] $users */
        $user = $this->repository->findUniqueBy(function (Query $query) {
            $query->where('name', '=', 'Unique name');
        });

        self::assertNull($user);
    }

    public function testFindUniqueByReturnsAbstractEntity()
    {
        $user = new TestEntity();
        $user->setName('Unique name');

        $this->repository->save($user);

        /** @var TestEntity $user */
        $user = $this->repository->findUniqueBy(function (Query $query) {
            $query->where('name', '=', 'Unique name');
        });

        self::assertInstanceOf(TestEntity::class, $user);
        self::assertSame('Unique name', $user->getName());
    }

    public function testFindUniqueByThrowsOnMoreThanOneResult()
    {
        self::expectException(Exception::class);
        self::expectExceptionMessage("Found more than one of");

        $user1 = new TestEntity();
        $user1->setName('Unique name');

        $user2 = new TestEntity();
        $user2->setName('Unique name');

        $this->repository->saveAll($user1, $user2);

        /** @var TestEntity[] $users */
        $this->repository->findUniqueBy(function (Query $query) {
            $query->where('name', '=', 'Unique name');
        });
    }

    public function testCountBy()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        $userCount = $this->repository->countAll();

        self::assertSame(4, $userCount);

        /** @var TestEntity[] $users */
        $userCount = $this->repository->countBy(function (Query $query) {
            $query->where('name', 'LIKE', 'User%');
        });

        self::assertSame(3, $userCount);
    }

    public function testSaveNewEntity()
    {
        $user = new TestEntity();
        $user->setName('First Custom User');

        $this->repository->save($user);

        self::assertContains('INSERT INTO `users`', $this->database->getLastQuery());

        self::assertCount(2, $users = $this->repository->findAll());

        /** @var TestEntity $user */
        $user = $users[1];

        self::assertNotNull($user->getId());
        self::assertSame('First Custom User', $user->getName());
    }

    public function testSaveExistingEntity()
    {
        self::assertCount(1, $users = $this->repository->findAll());

        $this->defaultUser->setName('Updated Default User');
        $this->repository->save($this->defaultUser);

        self::assertContains('UPDATE `users`', $this->database->getLastQuery());

        self::assertCount(1, $users = $this->repository->findAll());
    }

    public function testSaveExistingEntityOnlyUpdatesChangedValues()
    {
        self::assertCount(1, $users = $this->repository->findAll());

        $this->repository->save($this->defaultUser);

        $lastQuery = $this->database->getLastQuery();
        $queryParts = explode('WHERE', $lastQuery);
        $queryWithoutWhere = reset($queryParts);

        self::assertContains('`updated_at` =', $queryWithoutWhere);
        self::assertNotContains('`id` =', $queryWithoutWhere);
        self::assertNotContains('`name` =', $queryWithoutWhere);
        self::assertNotContains('`created_at` =', $queryWithoutWhere);

        self::assertCount(1, $users = $this->repository->findAll());
    }

    public function testSaveMultipleWorksAsExpectedWithNewEntities()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        self::assertSame(3, $this->database->getQueryCount());

        // 4, since the default user is also in there
        self::assertCount(4, $this->repository->findAll());
    }

    public function testSaveMultipleWorksAsExpectedWithExistingEntities()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        self::assertSame(3, $this->database->getQueryCount());

        // 4, since the default user is also in there
        self::assertCount(4, $this->repository->findAll());

        $user1->setName('User 1 Again');
        $user2->setName('User 2 Again');
        $user3->setName('User 3 Again');
    }

    public function testDeferSave()
    {
        self::assertCount(1, $this->repository->findAll());

        $user1 = new TestEntity();
        $user1->setName('User 1');

        $this->repository->deferSave($user1);

        self::assertCount(1, $this->repository->findAll());

        $this->repository->saveDeferred();

        /** @var TestEntity[] $users */
        self::assertCount(2, $users = $this->repository->findAll());

        self::assertEquals(
            $user1->getName(),
            $users[1]->getName()
        );
    }

public function testDeferSaveAllWithMultipleEntities()
{
    self::assertCount(1, $this->repository->findAll());

    $user1 = new TestEntity();
    $user2 = new TestEntity();
    $user3 = new TestEntity();

    $user1->setName('User 1');
    $user2->setName('User 2');
    $user3->setName('User 3');

    $this->repository->deferSave($user1, $user2, $user3);

    self::assertCount(1, $this->repository->findAll());

    $this->resetQueryCount($this->database);

    self::assertSame(0, $this->database->getQueryCount());

    $this->repository->saveDeferred();

    self::assertSame(1, $this->database->getQueryCount());

    /** @var TestEntity[] $users */
    self::assertCount(4, $users = $this->repository->findAll());

    self::assertEquals(
        $user1->getName(),
        $users[1]->getName()
    );

    self::assertEquals(
        $user2->getName(),
        $users[2]->getName()
    );

    self::assertEquals(
        $user3->getName(),
        $users[3]->getName()
    );
}

    public function testSaveMultipleDeferredWorksAsExpectedWithNewAndUpdatedEntities()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');

        $this->defaultUser->setName('Nope.');

        $this->repository->deferSave($user1, $this->defaultUser, $user2);

        self::assertCount(1, $this->repository->findAll());

        $this->repository->saveDeferred();

        self::assertSame(3, $this->database->getQueryCount());

        // 3, since the default user is also in there, and only 2 were added
        self::assertCount(3, $this->repository->findAll());
    }

    public function testSaveWithNoChangesDoesntActuallyRunQuery()
    {
        $repository = new class (...$this->container->getDependenciesFor(TestRepository::class)) extends TestRepository
        {
            public function getEntityClass(): string
            {
                return TestEntityWithoutTraits::class;
            }
        };

        $user = new TestEntityWithoutTraits();
        $user->setName('This has no auto-updates.');
        $user->setCreatedAt(date('Y-m-d H:i:s'));

        $repository->save($user);

        self::assertSame(1, $this->database->getQueryCount());

        self::assertNotNull($user->getId());

        $repository->save($user);

        self::assertSame(1, $this->database->getQueryCount());
    }

    public function testDelete()
    {
        self::assertCount(1, $this->repository->findAll());

        $this->repository->delete($this->defaultUser);

        self::assertCount(0, $this->repository->findAll());
    }

    public function testDeleteMultiple()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        self::assertCount(4, $this->repository->findAll());

        $this->repository->delete($user1, $user2, $user3);

        /** @var TestEntity[] $users */
        self::assertCount(1, $users = $this->repository->findAll());

        self::assertSame(
            $this->defaultUser->getName(),
            $users[0]->getName()
        );
    }

    public function testDeleteUnsavedEntitiesFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete entity that is not stored.');

        $user = new TestEntity();

        $this->repository->delete($user);
    }

    public function testDeleteUnknownEntitiesFails()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Expected 'Parable\Orm\Tests\Classes\TestEntity', got 'Parable\Orm\Tests\Classes\TestEntityWithoutTraits'"
            . ' instead. Cannot handle these classes.'
        );

        $user = new TestEntityWithoutTraits();

        $this->repository->delete($user);
    }

    public function testDeferDelete()
    {
        $this->repository->deferDelete($this->defaultUser);

        self::assertCount(1, $this->repository->findAll());

        $this->repository->deleteDeferred();

        self::assertCount(0, $users = $this->repository->findAll());
    }

    public function testDeferDeleteMultiple()
    {
        $user1 = new TestEntity();
        $user2 = new TestEntity();
        $user3 = new TestEntity();

        $user1->setName('User 1');
        $user2->setName('User 2');
        $user3->setName('User 3');

        $this->repository->saveAll($user1, $user2, $user3);

        $this->repository->deferDelete($user1, $user2, $user3);

        self::assertCount(4, $this->repository->findAll());

        $this->repository->deleteDeferred();

        self::assertCount(1, $users = $this->repository->findAll());
    }

    public function testNoPrimaryKeyPosesAProblem()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            "Primary key property 'id' does not exist on Entity Parable\Orm\Tests\Classes\TestEntityWithoutPrimaryKey"
        );

        /** @var TestRepositoryWithoutPrimaryKey $repository */
        $repository = $this->container->build(TestRepositoryWithoutPrimaryKey::class);

        $repository->findAll();
    }
}
