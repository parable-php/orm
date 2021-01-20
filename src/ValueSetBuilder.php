<?php declare(strict_types=1);

namespace Parable\Orm;

use Parable\Query\Query;
use Parable\Query\ValueSet;

class ValueSetBuilder
{
    public function build(
        AbstractRepository $repository,
        Query $query,
        AbstractEntity $entity
    ): ValueSet {
        switch ($query->getType()) {
            case Query::TYPE_UPDATE:
                return $this->buildValueSetForUpdate($repository, $entity);
            case Query::TYPE_INSERT:
                return $this->buildValueSetForInsert($repository, $entity);
        }

        throw new Exception(sprintf('Cannot build value set for Query of type %s', $query->getType()));
    }

    protected function buildValueSetForUpdate(
        AbstractRepository $repository,
        AbstractEntity $entity
    ): ValueSet {
        $values = $entity->toArrayWithOnlyChanges();

        return $this->buildValueSetWithoutPrimaryKey($repository->getPrimaryKey(), $values);
    }

    protected function buildValueSetForInsert(
        AbstractRepository $repository,
        AbstractEntity $entity
    ): ValueSet {
        $values = $entity->toArrayWithoutEmptyValues();

        return $this->buildValueSetWithoutPrimaryKey($repository->getPrimaryKey(), $values);
    }

    protected function buildValueSetWithoutPrimaryKey(
        string $primaryKey,
        array $values
    ): ValueSet {
        unset($values[$primaryKey]);

        return new ValueSet($values);
    }
}
