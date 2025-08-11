<?php

namespace App\Command\Equipment;

class AssignEquipmentCommand
{
    public function __construct(
        public readonly int $equipmentId,
        public readonly int $assignedToId,
        public readonly int $assignerId,
        public readonly string $notes = ''
    ) {
    }
}