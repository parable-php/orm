<?php declare(strict_types=1);

namespace Parable\Orm\Traits;

interface SupportsUpdatedAt
{
    public function setUpdatedAt(string $updatedAt): void;
    public function getUpdatedAt(): ?string;
    public function markUpdatedAt(): void;
}
