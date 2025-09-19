<?php

namespace App\Event\Equipment;

// DEPRECATED: Legacy EquipmentAssignedEvent - Equipment module disabled
// Use AsekuracyjnyEquipmentAssignedEvent or AparaturaPomiarowaEquipmentAssignedEvent instead

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @deprecated Equipment module disabled. Use specialized module events instead.
 */
class EquipmentAssignedEvent extends Event
{
    public const NAME = 'equipment.assigned';

    public function __construct()
    {
        throw new \Exception('EquipmentAssignedEvent is deprecated. Equipment module disabled. Use specialized module events instead.');
    }
}