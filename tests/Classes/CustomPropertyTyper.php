<?php declare(strict_types=1);

namespace Parable\Orm\Tests\Classes;

use Parable\Orm\PropertyTypes\PropertyTyper;

class CustomPropertyTyper implements PropertyTyper
{
    public function type($value)
    {
        return 'CUSTOMIZED/' . $value;
    }

    public function untype($value): string
    {
        return str_replace('CUSTOMIZED/', '', $value);
    }
}
