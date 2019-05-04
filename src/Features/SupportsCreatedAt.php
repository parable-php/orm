<?php declare(strict_types=1);

namespace Parable\Orm\Features;

interface SupportsCreatedAt
{
    public function setCreatedAt(string $createdAt): void;

    public function markCreatedAt(): void;
}
