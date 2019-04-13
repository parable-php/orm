<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use DateTimeImmutable;
use Parable\Orm\AbstractEntity;
use Parable\Orm\Database;
use Parable\Orm\Traits\SupportsCreatedAt;
use Parable\Orm\Traits\SupportsUpdatedAt;

class TestEntityWithMissingSetters extends AbstractEntity implements SupportsCreatedAt, SupportsUpdatedAt
{
    protected $id;
    protected $name;
    protected $created_at;
    protected $updated_at;

    /**
     * This constructor isn't necessary but for testing purposes
     * it's handy to be able to create a specific entity.
     */
    public function __construct(
        int $id = null,
        string $name = null,
        string $created_at = null,
        string $updated_at = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;

        $this->originalProperties = $this->toArray();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->created_at = $createdAt;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function markCreatedAt(): void
    {
        $this->created_at = (new DateTimeImmutable())->format(Database::DATETIME_SQL);
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updated_at = $updatedAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updated_at;
    }

    public function markUpdatedAt(): void
    {
        $this->updated_at = (new DateTimeImmutable())->format(Database::DATETIME_SQL);
    }
}
