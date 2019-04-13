<?php declare(strict_types=1);

namespace Parable\Orm\Traits;

interface SupportsCreatedAt
{
    public function setCreatedAt(string $createdAt): void;
    public function getCreatedAt(): ?string;
    public function markCreatedAt(): void;
}
