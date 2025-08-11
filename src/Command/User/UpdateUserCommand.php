<?php

namespace App\Command\User;

class UpdateUserCommand
{
    public function __construct(
        public readonly int $userId,
        public readonly array $data,
        public readonly int $updatedById
    ) {
    }
}