<?php declare(strict_types=1);

namespace Parable\Orm;

use DateTimeImmutable;
use Parable\Orm\Features\HasTypedProperties;

class PropertyTypeDeterminer
{
    public const TYPE_INT = 0;
    public const TYPE_DATETIME = 1;
    public const TYPE_DATE = 2;
    public const TYPE_TIME = 3;

    public static function typeProperty(AbstractEntity $entity, string $property, $value)
    {
        if (!($entity instanceof HasTypedProperties)) {
            return $value;
        }

        $type = $entity->getPropertyType($property);

        if ($type === null || $value === null) {
            return $value;
        }

        $value_original = $value;

        switch ($type) {
            case self::TYPE_INT:
                return (int)$value;

            case self::TYPE_DATE:
                $value = DateTimeImmutable::createFromFormat(Database::DATE_SQL, $value);

                if ($value === false) {
                    throw new Exception('Cannot type ' . $value_original . ' as DATE_SQL');
                }

                break;

            case self::TYPE_TIME:
                $value = DateTimeImmutable::createFromFormat(Database::TIME_SQL, $value);

                if ($value === false) {
                    throw new Exception('Cannot type ' . $value_original . ' as TIME_SQL');
                }

                break;

            case self::TYPE_DATETIME:
                $value = DateTimeImmutable::createFromFormat(Database::DATETIME_SQL, $value);

                if ($value === false) {
                    throw new Exception('Cannot type ' . $value_original . ' as DATETIME_SQL');
                }
        }

        return $value;
    }

    public static function untypeProperty(AbstractEntity $entity, string $property, $value)
    {
        if (!($entity instanceof HasTypedProperties)) {
            return $value;
        }

        $type = $entity->getPropertyType($property);

        if ($type === null || $value === null) {
            return $value;
        }

        switch ($type) {
            case self::TYPE_DATE:
                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::DATE_SQL);
                }

                break;

            case self::TYPE_TIME:
                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::TIME_SQL);
                }

                break;

            case self::TYPE_DATETIME:
                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::DATETIME_SQL);
                }

                break;
        }

        return $value;
    }
}
