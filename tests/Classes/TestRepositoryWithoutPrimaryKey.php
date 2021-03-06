<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use Parable\Orm\AbstractRepository;

class TestRepositoryWithoutPrimaryKey extends AbstractRepository
{
    public function getTableName(): string
    {
        return 'users';
    }

    public function getPrimaryKey(): string
    {
        return 'id';
    }

    public function getEntityClass(): string
    {
        return TestEntityWithoutPrimaryKey::class;
    }
}
