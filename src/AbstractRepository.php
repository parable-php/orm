<?php declare(strict_types=1);

namespace Parable\Orm;

use Parable\Di\Container;
use Parable\Orm\Features\SupportsCreatedAt;
use Parable\Orm\Features\SupportsUpdatedAt;
use Parable\Query\Builder;
use Parable\Query\Query;

abstract class AbstractRepository
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
     * @var ValueSetBuilder
     */
    protected $valueSetBuilder;

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * @var AbstractEntity[]
     */
    protected $deferredSaveEntities = [];

    /**
     * @var AbstractEntity[]
     */
    protected $deferredDeleteEntities = [];

    public function __construct(
        Container $container,
        Database $database,
        ValueSetBuilder $valueSetBuilder
    ) {
        $this->container = $container;
        $this->database = $database;
        $this->valueSetBuilder = $valueSetBuilder;

        $this->builder = new Builder($database->getConnection());
    }

    abstract public function getTableName(): string;

    abstract public function getPrimaryKey(): string;

    abstract public function getEntityClass(): string;

    public function createEntityClass(): AbstractEntity
    {
        $entityClass = $this->getEntityClass();

        if (!class_exists($entityClass)) {
            throw new Exception(sprintf("Traits class '%s' does not exist.", $entityClass));
        }

        $entity = $this->container->build($this->getEntityClass());

        if (!($entity instanceof AbstractEntity)) {
            throw new Exception(sprintf("Class '%s' does not extend AbstractEntity.", $entityClass));
        }

        return $entity;
    }

    /**
     * @return AbstractEntity[]
     */
    public function findAll(
        string $order = Query::ORDER_ASC,
        int $limit = 0,
        int $offset = 0
    ): array {
        $query = Query::select($this->getTableName());
        $query->orderBy($this->getPrimaryKey(), $order);
        $query->limit($limit, $offset);

        $entities = [];

        foreach ($result = $this->database->query($this->builder->build($query)) as $item) {
            $entities[] = $this->createEntityFromArrayItem($item);
        }

        return $entities;
    }

    public function countAll(): int
    {
        $query = Query::select($this->getTableName());
        $query->setColumns(['COUNT(1)']);

        $result = $this->database->query($this->builder->build($query));

        return (int)$result[0]['COUNT(1)'] ?? 0;
    }

    /**
     * @return AbstractEntity|null
     */
    public function find(int $id): ?AbstractEntity
    {
        $query = Query::select($this->getTableName());
        $query->where($this->getPrimaryKey(), '=', $id);
        $query->limit(1);

        $result = $this->database->query($this->builder->build($query));

        if (count($result) === 0) {
            return null;
        }

        $item = reset($result);

        return $this->createEntityFromArrayItem($item);
    }

    public function findUniqueBy(
        callable $callable
    ): ?AbstractEntity {
        $entities = $this->findBy($callable);

        if (count($entities) > 1) {
            throw new Exception(sprintf(
                "Found more than one of '%s'",
                $this->getEntityClass()
            ));
        }

        if (count($entities) === 0) {
            return null;
        }

        return $entities[0];
    }

    /**
     * @return AbstractEntity[]
     */
    public function findBy(
        callable $callable,
        string $order = Query::ORDER_ASC,
        int $limit = 0,
        int $offset = 0
    ): array {
        $query = Query::select($this->getTableName());
        $query->whereCallable($callable);
        $query->orderBy($this->getPrimaryKey(), $order);
        $query->limit($limit, $offset);

        $result = $this->database->query($this->builder->build($query));

        $entities = [];

        foreach ($result as $item) {
            $entities[] = $this->createEntityFromArrayItem($item);
        }

        return $entities;
    }

    public function countBy(
        callable $callable
    ): int {
        $query = Query::select($this->getTableName());
        $query->setColumns(['COUNT(1)']);
        $query->whereCallable($callable);

        $result = $this->database->query($this->builder->build($query));

        return (int)$result[0]['COUNT(1)'] ?? 0;
    }

    public function save(AbstractEntity $entity): AbstractEntity
    {
        $query = $this->createSaveQueryForEntity($entity);

        if (!$query->hasValueSets()) {
            return $entity;
        }

        $this->database->query($this->builder->build($query));

        if ($query->getType() === Query::TYPE_INSERT) {
            $key = $this->database->getConnection()->lastInsertId();

            $entity->setPrimaryKey($this->getPrimaryKey(), $key);
            $entity->markAsOriginal();
        }

        return $entity;
    }

    /**
     * @return AbstractEntity[]
     */
    public function saveAll(AbstractEntity ...$entities): array
    {
        $returnEntities = [];

        foreach ($entities as $entity) {
            $returnEntities[] = $this->save($entity);
        }

        return $returnEntities;
    }

    public function deferSave(AbstractEntity ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->deferredSaveEntities[] = $entity;
        }
    }

    public function saveDeferred(): void
    {
        $insertQuery = Query::insert($this->getTableName());

        foreach ($this->deferredSaveEntities as $entity) {
            // Update queries are immediate
            if ($this->isEntityStored($entity)) {
                $this->save($entity);

                continue;
            }

            $this->validateEntityCorrectClass($entity);

            if ($entity instanceof SupportsCreatedAt) {
                $entity->markCreatedAt();
            }

            $valueSet = $this->valueSetBuilder->build($this, $insertQuery, $entity);

            $insertQuery->addValueSet($valueSet);
        }

        if ($insertQuery->hasValueSets()) {
            $this->database->query($this->builder->build($insertQuery));
        }

        $this->clearDeferredSaves();
    }

    public function clearDeferredSaves(): void
    {
        $this->deferredSaveEntities = [];
    }

    public function delete(AbstractEntity ...$entities): void
    {
        $this->deleteByPrimaryKeys(
            $this->getPrimaryKeysFromEntities($entities)
        );
    }

    public function deferDelete(AbstractEntity ...$entities): void
    {
        foreach ($entities as $entity) {
            $this->deferredDeleteEntities[] = $entity;
        }
    }

    public function deleteDeferred(): void
    {
        $this->deleteByPrimaryKeys(
            $this->getPrimaryKeysFromEntities($this->deferredDeleteEntities)
        );

        $this->clearDeferredDeletes();
    }

    public function clearDeferredDeletes(): void
    {
        $this->deferredDeleteEntities = [];
    }

    protected function createSaveQueryForEntity(AbstractEntity $entity): Query
    {
        $this->validateEntityCorrectClass($entity);

        if (!$this->isEntityStored($entity)) {
            $query = Query::insert($this->getTableName());

            if ($entity instanceof SupportsCreatedAt) {
                $entity->markCreatedAt();
            }
        } else {
            $query = Query::update($this->getTableName());
            $query->where($this->getPrimaryKey(), '=', $entity->getPrimaryKey($this->getPrimaryKey()));

            if ($entity instanceof SupportsUpdatedAt) {
                $entity->markUpdatedAt();
            }
        }

        $valueSet = $this->valueSetBuilder->build($this, $query, $entity);

        if ($valueSet->hasValues()) {
            $query->addValueSet($valueSet);
        }

        return $query;
    }

    protected function deleteByPrimaryKeys(array $primaryKeys): void
    {
        $query = Query::delete($this->getTableName());
        $query->where($this->getPrimaryKey(), 'IN', $primaryKeys);

        $this->database->query($this->builder->build($query));
    }

    protected function createEntityFromArrayItem(array $item): AbstractEntity
    {
        return $this->getEntityClass()::fromDatabaseItem($this->getPrimaryKey(), $item);
    }

    protected function getPrimaryKeysFromEntities(array $entities): array
    {
        $primaryKeys = [];

        foreach ($entities as $entity) {
            $this->validateEntityCorrectClass($entity);

            if (!$this->isEntityStored($entity)) {
                throw new Exception('Cannot delete entity that is not stored.');
            }

            $primaryKeys[] = $entity->getPrimaryKey($this->getPrimaryKey());
        }

        return $primaryKeys;
    }

    protected function isEntityStored(AbstractEntity $entity): bool
    {
        return $entity->getPrimaryKey($this->getPrimaryKey()) !== null;
    }

    protected function validateEntityCorrectClass(AbstractEntity $entity): void
    {
        $entityClass = $this->getEntityClass();

        if ($entity instanceof $entityClass) {
            return;
        }

        throw new Exception(sprintf(
            "Expected '%s', got '%s' instead. Cannot handle these classes.",
            $entityClass,
            get_class($entity)
        ));
    }
}
