<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use Parable\Orm\AbstractRepository;

class TestRepositoryForTyped extends AbstractRepository
{
    public function getTableName(): string
    {
        return 'types';
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    public function getEntityClass(): string
    {
        return TestEntityWithTypedProperties::class;
    }
}
