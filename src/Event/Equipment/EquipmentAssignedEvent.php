<?php

namespace App\Event\Equipment;

use App\Entity\Equipment;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class EquipmentAssignedEvent extends Event
{
    public const NAME = 'equipment.assigned';

    public function __construct(
        private Equipment $equipment,
        private User $assignedTo,
        private ?User $previousAssignee,
        private User $assignedBy,
        private string $notes = '',
        private array $context = []
    ) {
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
    }

    public function getAssignedTo(): User
    {
        return $this->assignedTo;
    }

    public function getPreviousAssignee(): ?User
    {
        return $this->previousAssignee;
    }

    public function getAssignedBy(): User
    {
        return $this->assignedBy;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}