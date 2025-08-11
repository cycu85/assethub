<?php

namespace App\Event\User;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserCreatedEvent extends Event
{
    public const NAME = 'user.created';

    public function __construct(
        private User $user,
        private User $createdBy,
        private array $context = []
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}