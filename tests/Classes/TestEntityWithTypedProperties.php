<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use DateTimeImmutable;
use Parable\Orm\AbstractEntity;
use Parable\Orm\Features\HasTypedProperties;
use Parable\Orm\Features\SupportsUpdatedAt;
use Parable\Orm\PropertyTypes\BooleanPropertyTyper;
use Parable\Orm\PropertyTypes\DatePropertyTyper;
use Parable\Orm\PropertyTypes\DateTimePropertyTyper;
use Parable\Orm\PropertyTypes\IntegerPropertyTyper;
use Parable\Orm\PropertyTypes\TimePropertyTyper;

class TestEntityWithTypedProperties extends AbstractEntity implements SupportsUpdatedAt, HasTypedProperties
{
    protected $id;
    protected $boolean;
    protected $date;
    protected $time;
    protected $datetime;
    protected $updated_at;

    public function with(
        $id = null,
        $boolean = null,
        $date = null,
        $time = null,
        $datetime = null,
        $updated_at = null
    ): void {
        $this->id = $id;
        $this->boolean = $boolean;
        $this->date = $date;
        $this->time = $time;
        $this->datetime = $datetime;
        $this->updated_at = $updated_at;
    }

    public function getPropertyType(string $property): ?string
    {
        switch ($property) {
            case 'id':
                return IntegerPropertyTyper::class;

            case 'boolean':
                return BooleanPropertyTyper::class;

            case 'date':
                return DatePropertyTyper::class;

            case 'time':
                return TimePropertyTyper::class;

            case 'datetime':
            case 'updated_at':
                return DateTimePropertyTyper::class;
        }

        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getBoolean(): bool
    {
        return $this->boolean;
    }

    /**
     * @param mixed $boolean
     */
    public function setBoolean(bool $boolean): void
    {
        $this->boolean = $boolean;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getTime(): ?DateTimeImmutable
    {
        return $this->time;
    }

    public function setTime(DateTimeImmutable $time): void
    {
        $this->time = $time;
    }

    public function getDatetime(): ?DateTimeImmutable
    {
        return $this->datetime;
    }

    public function setDatetime(DateTimeImmutable $datetime): void
    {
        $this->datetime = $datetime;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeImmutable $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function markUpdatedAt(): void
    {
        $this->setUpdatedAt(new DateTimeImmutable());
    }
}
