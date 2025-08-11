<?php

namespace App\Query\User;

class GetUsersQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $limit = 25,
        public readonly ?string $search = null,
        public readonly ?string $department = null,
        public readonly ?bool $isActive = null,
        public readonly ?string $sortBy = 'lastName',
        public readonly string $sortDirection = 'ASC'
    ) {
    }

    public function getFilters(): array
    {
        return array_filter([
            'search' => $this->search,
            'department' => $this->department,
            'active' => $this->isActive !== null ? ($this->isActive ? 'true' : 'false') : null
        ]);
    }
}