<?php declare(strict_types=1);

namespace Parable\Orm\Tests;

use DateTimeImmutable;
use Parable\Di\Container;
use Parable\Orm\Database;
use Parable\Orm\Exception;
use Parable\Orm\Tests\Classes\TestEntity;
use Parable\Orm\Tests\Classes\TestRepository;
use Parable\Orm\ValueSetBuilder;
use Parable\Query\Query;

class ValueSetBuilderTest extends \PHPUnit\Framework\TestCase
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
     * @var ValueSetBuilder
     */
    protected $valueSetBuilder;

    public function setUp()
    {
        parent::setUp();

        $this->container = new Container();

        $this->database = $this->container->get(Database::class);

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

        $this->valueSetBuilder = new ValueSetBuilder();
    }

    public function testBasicUsageWithInsert()
    {
        $query = Query::insert($this->repository->getTableName());

        /** @var TestEntity $entity */
        $entity = $this->repository->createEntityClass();
        $entity->setName('Hello');

        $valueSet = $this->valueSetBuilder->build(
            $this->repository,
            $query,
            $entity
        );

        self::assertSame(
            [
                'name' => 'Hello',
            ],
            $valueSet->getValues()
        );
    }

    public function testInsertValueSetRemovesPrimaryKey()
    {
        $query = Query::insert($this->repository->getTableName());

        // Normally this wouldn't be a scenario one should want, unless one wishes to overwrite an existing row.
        $entity = new TestEntity();
        $entity->setPrimaryKey($this->repository->getPrimaryKey(), 1);
        $entity->setName('Hello');

        $valueSet = $this->valueSetBuilder->build(
            $this->repository,
            $query,
            $entity
        );

        self::assertSame(
            [
                'name' => 'Hello',
            ],
            $valueSet->getValues()
        );
    }

    public function testBasicUsageWithUpdateQuery()
    {
        $query = Query::update($this->repository->getTableName());

        /** @var TestEntity $entity */
        $entity = $this->repository->createEntityClass();
        $entity->setName('Hello');

        $this->repository->save($entity);

        $entity->setName('Hello Again');

        // The repository usually does this as part of save() by calling markUpdatedAt()
        $now = (new DateTimeImmutable())->format(Database::DATETIME_SQL);
        $entity->setUpdatedAt($now);

        $valueSet = $this->valueSetBuilder->build(
            $this->repository,
            $query,
            $entity
        );

        self::assertSame(
            [
                'name' => 'Hello Again',
                'updated_at' => $now,
            ],
            $valueSet->getValues()
        );
    }

    public function testNonUpdateOrInsertQueryBreaks()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot build value set for Query of type SELECT');

        $query = Query::select($this->repository->getTableName());

        $this->valueSetBuilder->build($this->repository, $query, new TestEntity());
    }
}
