<?php

namespace App\Event\Equipment;

// DEPRECATED: Legacy EquipmentCreatedEvent - Equipment module disabled
// Use AsekuracyjnyEquipmentCreatedEvent or AparaturaPomiarowaEquipmentCreatedEvent instead

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @deprecated Equipment module disabled. Use specialized module events instead.
 */
class EquipmentCreatedEvent extends Event
{
    public const NAME = 'equipment.created';

    public function __construct()
    {
        throw new \Exception('EquipmentCreatedEvent is deprecated. Equipment module disabled. Use specialized module events instead.');
    }
}