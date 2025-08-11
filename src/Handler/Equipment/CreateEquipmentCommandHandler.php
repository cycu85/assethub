<?php

namespace App\Handler\Equipment;

use App\Command\Equipment\CreateEquipmentCommand;
use App\Entity\Equipment;
use App\Repository\UserRepository;
use App\Service\EquipmentService;

class CreateEquipmentCommandHandler
{
    public function __construct(
        private EquipmentService $equipmentService,
        private UserRepository $userRepository
    ) {
    }

    public function handle(CreateEquipmentCommand $command): Equipment
    {
        $creator = $this->userRepository->find($command->createdById);
        if (!$creator) {
            throw new \InvalidArgumentException('Creator not found');
        }

        return $this->equipmentService->createEquipment($command->toArray(), $creator);
    }
}