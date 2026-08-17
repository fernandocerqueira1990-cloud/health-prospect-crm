<?php

namespace App\Services;

class ImportExecutionEntityRegistry
{
    /** @var array<int, array<string, int>> */
    private array $entities = [];

    public function remember(int $rowId, string $group, int $entityId): void
    {
        $this->entities[$rowId][$group] = $entityId;
    }

    public function get(int $rowId, string $group): ?int
    {
        return $this->entities[$rowId][$group] ?? null;
    }
}
