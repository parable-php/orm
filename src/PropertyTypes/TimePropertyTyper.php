<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use DateTimeImmutable;
use DateTimeInterface;
use Parable\Orm\Database;
use Parable\Orm\Exception;

class TimePropertyTyper implements PropertyTyper
{
    public function type($value): DateTimeImmutable
    {
        $time = DateTimeImmutable::createFromFormat(Database::TIME_SQL, $value);

        if ($time === false) {
            throw new Exception(sprintf(
                "Could not type '%s' as time with format %s",
                $value,
                Database::TIME_SQL
            ));
        }

        return $time;
    }

    public function untype($value): string
    {
        if (!($value instanceof DateTimeInterface)) {
            throw new Exception(sprintf(
                "Could not untype '%s' as time from DateTimeInterface with format %s",
                $value,
                Database::TIME_SQL
            ));
        }

        return $value->format(Database::TIME_SQL);
    }
}
