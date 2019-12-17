<?php declare(strict_types=1);

namespace Parable\Orm\Features;

interface HasTypedProperties
{
    public function getPropertyType(string $property): ?int;
}
