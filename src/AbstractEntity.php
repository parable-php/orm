<?php declare(strict_types=1);

namespace Parable\Orm;

abstract class AbstractEntity
{
    /**
     * @var string[]
     */
    protected $originalProperties = [];

    public function getPrimaryKey(string $key)
    {
        $this->validatePrimaryKey($key);

        return $this->{$key};
    }

    public function setPrimaryKey(string $key, $value): void
    {
        $this->{$key} = $value;
    }

    public function markAsOriginal(): void
    {
        $this->originalProperties = $this->toArray();
    }

    public static function fromDatabaseItem(string $primaryKey, array $values): self
    {
        $entity = new static();

        $entity->validatePrimaryKey($primaryKey);

        $entity->{$primaryKey} = $values[$primaryKey] ?? null;

        if ($entity->{$primaryKey} === null) {
            throw new Exception(sprintf(
                "Could not set primary key '%s' on Entity %s from values",
                $primaryKey,
                static::class
            ));
        }

        foreach ($values as $property => $value) {
            if (!property_exists($entity, $property)) {
                throw new Exception(sprintf(
                    "Property '%s' does not exist on Entity %s",
                    $property,
                    static::class
                ));
            }

            if ($property === $primaryKey) {
                continue;
            }

            if (strpos($property, '_') !== false) {
                $setter = 'set';

                $propertyParts = explode('_', $property);
                foreach ($propertyParts as $propertyPart) {
                    $setter .= ucfirst($propertyPart);
                }
            } else {
                $setter = 'set' . ucfirst($property);
            }

            if (!method_exists($entity, $setter)) {
                throw new Exception(sprintf(
                    "Setter method '%s' not defined on Entity %s",
                    $setter,
                    static::class
                ));
            }

            if ($value !== null) {
                $entity->{$setter}($value);
            }
        }

        $entity->markAsOriginal();

        return $entity;
    }

    public function validatePrimaryKey(string $key): void
    {
        if (!property_exists($this, $key)) {
            throw new Exception(sprintf(
                "Primary key property '%s' does not exist on Entity %s",
                $key,
                static::class
            ));
        }
    }

    public function toArray(): array
    {
        $array = (array)$this;

        $filtered = [];
        foreach ($array as $key => $value) {
            $key = str_replace('*', '', $key);
            $key = trim(str_replace(static::class, '', $key));

            if ($value !== null && !is_scalar($value)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    public function toArrayWithoutEmptyValues(): array
    {
        $array = $this->toArray();

        foreach ($array as $key => $value) {
            if (empty($value)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function toArrayWithOnlyChanges(): array
    {
        $array = $this->toArray();

        foreach ($array as $key => $value) {
            if (array_key_exists($key, $this->originalProperties)
                && $this->originalProperties[$key] === $value
            ) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    public function getProperties(): array
    {
        return array_keys($this->toArray());
    }
}
