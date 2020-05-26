<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use Parable\Orm\Exception;

class IntegerPropertyTyper implements PropertyTyper
{
    public function type($value): int
    {
        if (!is_numeric($value)) {
            throw new Exception(sprintf(
                "Could not type '%s' as integer",
                $value
            ));
        }

        return (int)$value;
    }

    public function untype($value): string
    {
        if (!is_int($value)) {
            throw new Exception(sprintf(
                "Could not untype '%s' from integer",
                $value
            ));
        }

        return (string)$value;
    }
}
