<?php

namespace App\Handler\User;

use App\Command\User\CreateUserCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;

class CreateUserCommandHandler
{
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepository
    ) {
    }

    public function handle(CreateUserCommand $command): User
    {
        $creator = $this->userRepository->find($command->createdById);
        if (!$creator) {
            throw new \InvalidArgumentException('Creator not found');
        }

        return $this->userService->createUser($command->toArray(), $creator);
    }
}