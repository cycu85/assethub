<?php

namespace App\Event\Equipment;

use App\Entity\Equipment;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class EquipmentCreatedEvent extends Event
{
    public const NAME = 'equipment.created';

    public function __construct(
        private Equipment $equipment,
        private User $createdBy,
        private array $context = []
    ) {
    }

    public function getEquipment(): Equipment
    {
        return $this->equipment;
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