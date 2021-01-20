<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use DateTimeImmutable;
use Parable\Orm\AbstractEntity;
use Parable\Orm\Database;
use Parable\Orm\Features\SupportsCreatedAt;
use Parable\Orm\Features\SupportsUpdatedAt;

class TestEntity extends AbstractEntity implements SupportsCreatedAt, SupportsUpdatedAt
{
    /**
     * This constructor isn't necessary but for testing purposes
     * it's handy to be able to create a specific entity.
     */
    public function __construct(
        protected mixed $id = null,
        protected ?string $name = null,
        protected ?string $created_at = null,
        protected ?string $updated_at = null
    ) {
        $this->originalProperties = $this->toArray();
    }

    public function unmarkAsOriginal(): void
    {
        $this->originalProperties = [];
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->created_at = $createdAt;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function markCreatedAt(): void
    {
        $this->setCreatedAt((new DateTimeImmutable())->format(Database::DATETIME_SQL));
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updated_at = $updatedAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function markUpdatedAt(): void
    {
        $this->setUpdatedAt((new DateTimeImmutable())->format(Database::DATETIME_SQL));
    }
}
