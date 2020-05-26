<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use DateTimeImmutable;
use DateTimeInterface;
use Parable\Orm\Database;
use Parable\Orm\Exception;

class DatePropertyTyper implements PropertyTyper
{
    public function type($value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(Database::DATE_SQL, $value);

        if ($date === false) {
            throw new Exception(sprintf(
                "Could not type '%s' as date with format %s",
                $value,
                Database::DATE_SQL
            ));
        }

        return $date;
    }

    public function untype($value): string
    {
        if (!($value instanceof DateTimeInterface)) {
            throw new Exception(sprintf(
                "Could not untype '%s' as date from DateTimeInterface with format %s",
                $value,
                Database::DATE_SQL
            ));
        }

        return $value->format(Database::DATE_SQL);
    }
}
