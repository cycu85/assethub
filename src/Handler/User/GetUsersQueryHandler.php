<?php

namespace App\Handler\User;

use App\Query\User\GetUsersQuery;
use App\Service\UserService;

class GetUsersQueryHandler
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function handle(GetUsersQuery $query): object
    {
        return $this->userService->getUsersWithPagination(
            $query->page,
            $query->limit,
            $query->getFilters()
        );
    }
}