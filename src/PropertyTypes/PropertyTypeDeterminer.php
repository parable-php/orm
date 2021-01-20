<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use Parable\Orm\AbstractEntity;
use Parable\Orm\Features\HasTypedProperties;

class PropertyTypeDeterminer
{
    public static function typeProperty(AbstractEntity $entity, string $property, $value)
    {
        if ($value === null) {
            return null;
        }

        $transformer = static::getTransformerFromEntity($entity, $property);

        if ($transformer === null) {
            return $value;
        }

        return $transformer->type($value);
    }

    public static function untypeProperty(AbstractEntity $entity, string $property, $value)
    {
        if ($value === null) {
            return null;
        }

        $transformer = static::getTransformerFromEntity($entity, $property);

        if ($transformer === null) {
            return $value;
        }

        return $transformer->untype($value);
    }

    protected static function getTransformerFromEntity(AbstractEntity $entity, string $property): ?PropertyTyper
    {
        if (!($entity instanceof HasTypedProperties)) {
            return null;
        }

        $transformerClass = $entity->getPropertyType($property);

        if ($transformerClass === null) {
            return null;
        }

        return new $transformerClass();
    }
}
