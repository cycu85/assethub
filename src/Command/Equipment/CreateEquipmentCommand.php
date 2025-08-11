<?php

namespace App\Command\Equipment;

class CreateEquipmentCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly ?string $inventoryNumber = null,
        public readonly ?string $serialNumber = null,
        public readonly ?string $manufacturer = null,
        public readonly ?string $model = null,
        public readonly ?int $categoryId = null,
        public readonly string $status = 'available',
        public readonly ?float $purchasePrice = null,
        public readonly ?\DateTime $purchaseDate = null,
        public readonly ?\DateTime $warrantyExpiry = null,
        public readonly ?string $location = null,
        public readonly ?int $assignedToId = null,
        public readonly int $createdById
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'inventoryNumber' => $this->inventoryNumber,
            'serialNumber' => $this->serialNumber,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'category' => $this->categoryId,
            'status' => $this->status,
            'purchasePrice' => $this->purchasePrice,
            'purchaseDate' => $this->purchaseDate,
            'warrantyExpiry' => $this->warrantyExpiry,
            'location' => $this->location
        ];
    }
}