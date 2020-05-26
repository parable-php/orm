<?php declare(strict_types=1);

namespace Parable\Orm\PropertyTypes;

interface PropertyTyper
{
    public function type($value);

    public function untype($value): string;
}
