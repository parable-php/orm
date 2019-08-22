<?php declare(strict_types=1);

namespace Parable\Orm\Features;

interface SupportsUpdatedAt
{
    public function markUpdatedAt(): void;
}
