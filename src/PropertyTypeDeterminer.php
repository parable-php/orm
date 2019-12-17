<?php declare(strict_types=1);

namespace Parable\Orm;

use DateTimeImmutable;
use Parable\Orm\Features\HasTypedProperties;

class PropertyTypeDeterminer
{
    public const TYPE_INT = 0;
    public const TYPE_DATE = 1;
    public const TYPE_TIME = 2;
    public const TYPE_DATETIME = 3;

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
                if (!is_numeric($value)) {
                    throw new Exception(sprintf(
                        "Could not type '%s' as TYPE_INT",
                        $value_original
                    ));
                }

                return (int)$value;

            case self::TYPE_DATE:
                $value = DateTimeImmutable::createFromFormat(Database::DATE_SQL, $value);

                if ($value === false) {
                    throw new Exception(sprintf(
                        "Could not type '%s' as TYPE_DATE with format %s",
                        $value_original,
                        Database::DATE_SQL
                    ));
                }

                break;

            case self::TYPE_TIME:
                $value = DateTimeImmutable::createFromFormat(Database::TIME_SQL, $value);

                if ($value === false) {
                    throw new Exception(sprintf(
                        "Could not type '%s' as TYPE_TIME with format %s",
                        $value_original,
                        Database::TIME_SQL
                    ));
                }

                break;

            case self::TYPE_DATETIME:
                $value = DateTimeImmutable::createFromFormat(Database::DATETIME_SQL, $value);

                if ($value === false) {
                    throw new Exception(sprintf(
                        "Could not type '%s' as TYPE_DATETIME with format %s",
                        $value_original,
                        Database::DATETIME_SQL
                    ));
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
            case self::TYPE_INT:
                $type = 'TYPE_INT';

                if (is_numeric($value)) {
                    return (int)$value;
                }

                break;

            case self::TYPE_DATE:
                $type = 'TYPE_DATE';

                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::DATE_SQL);
                }

                break;

            case self::TYPE_TIME:
                $type = 'TYPE_TIME';

                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::TIME_SQL);
                }

                break;

            case self::TYPE_DATETIME:
                $type = 'TYPE_DATETIME';

                if ($value instanceof DateTimeImmutable) {
                    return $value->format(Database::DATETIME_SQL);
                }

                break;
        }

        throw new Exception(sprintf(
            "Could not untype '%s' as %s",
            $value,
            $type
        ));
    }
}
