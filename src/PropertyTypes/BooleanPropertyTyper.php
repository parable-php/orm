<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

use Parable\Orm\Exception;

class BooleanPropertyTyper implements PropertyTyper
{
    public function type($value): bool
    {
        if ($value !== '1' && $value !== '0') {
            throw new Exception(sprintf(
                "Could not type '%s' as boolean",
                $value
            ));
        }

        return (bool)$value;
    }

    public function untype($value): string
    {
        if (!is_bool($value)) {
            throw new Exception(sprintf(
                "Could not untype '%s' from boolean",
                $value
            ));
        }

        return $value ? '1' : '0';
    }
}
