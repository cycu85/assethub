<?php

namespace App\Query\Equipment;

class GetEquipmentQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 25,
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $assignedToId = null,
        public readonly ?bool $warrantyExpiring = null,
        public readonly ?string $sortBy = 'createdAt',
        public readonly string $sortDirection = 'DESC'
    ) {
    }

    public function getFilters(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->status,
            'category' => $this->categoryId,
            'assigned_to' => $this->assignedToId,
            'warranty_expiring' => $this->warrantyExpiring
        ]);
    }
}