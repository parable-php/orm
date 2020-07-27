<?php declare(strict_types=1);

namespace Parable\Orm;

use Parable\Di\Container;
use Parable\Orm\PropertyTypes\PropertyTypeDeterminer;

abstract class AbstractEntity
{
    /** @var string[] */
    protected $originalProperties = [];

    public function getPrimaryKey(string $key)
    {
        $this->validatePrimaryKeyExistsOnEntity($key);

        return $this->{$key};
    }

    public function setPrimaryKey(string $key, $value): void
    {
        $this->{$key} = $value;
    }

    public function hasBeenMarkedAsOriginal(): bool
    {
        return $this->originalProperties !== [];
    }

    public static function fromDatabaseItem(Container $container, string $primaryKey, array $values): self
    {
        $entity = $container->build(static::class);

        $entity->validatePrimaryKeyExistsOnEntity($primaryKey);

        if (!isset($values[$primaryKey])) {
            throw new Exception(sprintf(
                "Could not set primary key '%s' on entity %s from database values",
                $primaryKey,
                static::class
            ));
        }

        $primaryKeyValue = PropertyTypeDeterminer::typeProperty($entity, $primaryKey, $values[$primaryKey] ?? null);

        $entity->setPrimaryKey($primaryKey, $primaryKeyValue ?? null);

        foreach ($values as $property => $value) {
            if (!property_exists($entity, $property)) {
                throw new Exception(sprintf(
                    "Property '%s' does not exist on entity %s",
                    $property,
                    static::class
                ));
            }

            if ($property === $primaryKey || $value === null) {
                continue;
            }

            $setter = $entity->getSetterForProperty($property);

            $entity->{$setter}(PropertyTypeDeterminer::typeProperty($entity, $property, $value));
        }

        $entity->markAsOriginal();

        return $entity;
    }

    public function toArray(): array
    {
        $array = (array)$this;

        $filtered = [];
        foreach ($array as $key => $value) {
            $key = str_replace('*', '', $key);
            $key = trim(str_replace(static::class, '', $key));

            $value = PropertyTypeDeterminer::untypeProperty($this, $key, $value);

            if ($value !== null && !is_scalar($value)) {
                continue;
            }

            $filtered[$key] = $value;
        }

        return $filtered;
    }

    public function toArrayWithout(string ...$keys): array
    {
        $array = $this->toArray();

        foreach ($keys as $key) {
            unset($array[$key]);
        }

        return $array;
    }

    public function toArrayWithoutEmptyValues(): array
    {
        $array = $this->toArray();

        foreach ($array as $key => $value) {
            // We don't want values 0 or '0' to be seen as empty, so we ctype_digit it
            if (empty($value) && !ctype_digit($value)) {
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

    public function markAsOriginal(): void
    {
        $this->originalProperties = $this->toArray();
    }

    protected function getSetterForProperty(string $property): string
    {
        if (strpos($property, '_') !== false) {
            $setter = 'set';

            $propertyParts = explode('_', $property);
            foreach ($propertyParts as $propertyPart) {
                $setter .= ucfirst($propertyPart);
            }
        } else {
            $setter = 'set' . ucfirst($property);
        }

        if (!method_exists($this, $setter)) {
            throw new Exception(sprintf(
                "Setter method '%s' not defined on entity %s",
                $setter,
                static::class
            ));
        }

        return $setter;
    }

    protected function validatePrimaryKeyExistsOnEntity(string $key): void
    {
        if (!property_exists($this, $key)) {
            throw new Exception(sprintf(
                "Primary key property '%s' does not exist on entity %s",
                $key,
                static::class
            ));
        }
    }
}
