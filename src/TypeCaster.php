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
     * a string, including float values.
     *
     * @return int|DateTimeImmutable|string
     */
    public static function castStringToType(string $value)
    {
        if (!self::$is_enabled) {
            return $value;
        }

        if (self::isInteger($value)) {
            return (int)$value;
        }

        if (self::isFloat($value)) {
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

    public static function isInteger(string $value): bool
    {
        return !self::isNonDecimalInteger($value) && ctype_digit($value) && is_numeric($value);
    }

    public static function isNonDecimalInteger(string $value): bool
    {
        return strpos($value, '0') === 0 && (string)(int)$value !== $value;
    }

    public static function isFloat(string $value): bool
    {
        return !self::isInteger($value) && !self::isNonDecimalInteger($value) && is_numeric($value);
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
