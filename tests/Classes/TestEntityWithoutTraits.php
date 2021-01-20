<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use Parable\Orm\AbstractEntity;

class TestEntityWithoutTraits extends AbstractEntity
{
    protected ?string $created_at;
    protected ?string $updated_at;

    /**
     * This constructor isn't necessary but for testing purposes
     * it's handy to be able to create a specific entity.
     */
    public function __construct(
        protected mixed $id = null,
        protected ?string $name = null
    ) {
        $this->originalProperties = $this->toArray();
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

    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updated_at = $updatedAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }
}
