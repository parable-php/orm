<?php declare(strict_types=1);

namespace Parable\Orm;

use DateTimeImmutable;

class TypeCaster
{
    private static $is_enabled = true;

    public static function enable(): void
    {
        self::$is_enabled = true;
    }

    public static function disable(): void
    {
        self::$is_enabled = false;
    }

    /**
     * We attempt to very carefully infer the type of a string value and return the
     * appropriate php typed value back. Anything not explicitly recognized stays
     * a string.
     *
     * @return DateTimeImmutable|string
     */
    public static function castStringToType(string $value)
    {
        if (!self::$is_enabled) {
            return $value;
        }

        if (self::isDate($value)) {
            return DateTimeImmutable::createFromFormat(Database::DATE_SQL, $value);
        }

        if (self::isTime($value)) {
            return DateTimeImmutable::createFromFormat(Database::TIME_SQL, $value);
        }

        if (self::isDateTime($value)) {
            return DateTimeImmutable::createFromFormat(Database::DATETIME_SQL, $value);
        }

        return $value;
    }

    public static function isDate(string $value): bool
    {
        return (bool)DateTimeImmutable::createFromFormat(Database::DATE_SQL, $value);
    }

    public static function isTime(string $value): bool
    {
        return (bool)DateTimeImmutable::createFromFormat(Database::TIME_SQL, $value);
    }

    public static function isDateTime(string $value): bool
    {
        return (bool)DateTimeImmutable::createFromFormat(Database::DATETIME_SQL, $value);
    }
}
