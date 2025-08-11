<?php

namespace App\Event\User;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class UserUpdatedEvent extends Event
{
    public const NAME = 'user.updated';

    public function __construct(
        private User $user,
        private User $updatedBy,
        private array $changes = [],
        private array $context = []
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUpdatedBy(): User
    {
        return $this->updatedBy;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}