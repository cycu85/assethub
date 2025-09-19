<?php

namespace App\Repository;

// Legacy Equipment and EquipmentAttachment entities removed - module disabled
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * DEPRECATED: Legacy EquipmentAttachmentRepository - Equipment module disabled
 * Use AsekuracyjnyEquipmentRepository or AparaturaPomiarowaEquipmentRepository instead
 */
class EquipmentAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // This class is deprecated - do not use
        throw new \Exception('EquipmentAttachmentRepository is deprecated. Equipment module disabled. Use specialized module repositories instead.');
    }

    // All methods removed - class deprecated
}