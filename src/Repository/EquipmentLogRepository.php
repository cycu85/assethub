<?php

namespace App\Repository;

// Legacy Equipment and EquipmentLog entities removed - module disabled
// This repository is now deprecated and should not be used
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DEPRECATED: Legacy EquipmentLogRepository - Equipment module disabled
 * Use AsekuracyjnyEquipmentRepository or AparaturaPomiarowaEquipmentRepository instead
 */
class EquipmentLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // This class is deprecated - do not use
        throw new \Exception('EquipmentLogRepository is deprecated. Equipment module disabled. Use specialized module repositories instead.');
    }

    // All methods removed - class deprecated
}