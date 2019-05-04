<?php declare(strict_types=1);

namespace Parable\Orm\Features;

interface SupportsUpdatedAt
{
    public function setUpdatedAt(string $updatedAt): void;

    public function markUpdatedAt(): void;
}
