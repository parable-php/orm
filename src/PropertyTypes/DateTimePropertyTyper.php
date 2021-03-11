<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use DateTimeImmutable;
use DateTimeInterface;
use Parable\Orm\Database;
use Parable\Orm\OrmException;

class DateTimePropertyTyper implements PropertyTyper
{
    public function type($value): DateTimeImmutable
    {
        $datetime = DateTimeImmutable::createFromFormat(Database::DATETIME_SQL, $value);

        if ($datetime === false) {
            throw new OrmException(sprintf(
                "Could not type '%s' as datetime with format %s",
                $value,
                Database::DATETIME_SQL
            ));
        }

        return $datetime;
    }

    public function untype($value): string
    {
        if (!($value instanceof DateTimeInterface)) {
            throw new OrmException(sprintf(
                "Could not untype '%s' as datetime from DateTimeInterface with format %s",
                $value,
                Database::DATETIME_SQL
            ));
        }

        return $value->format(Database::DATETIME_SQL);
    }
}
