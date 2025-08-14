<?php

namespace App\AsekuracyjnySPM\Entity;

use App\AsekuracyjnySPM\Repository\AsekuracyjnyTransferRepository;
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AsekuracyjnyTransferRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'asekuracyjny_transfer')]
class AsekuracyjnyTransfer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $transferNumber = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyEquipment::class, inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AsekuracyjnyEquipment $equipment = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyEquipmentSet::class, inversedBy: 'transfers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AsekuracyjnyEquipmentSet $equipmentSet = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Odbiorca jest wymagany')]
    private ?User $recipient = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Data przekazania jest wymagana')]
    private ?\DateTimeInterface $transferDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $returnDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $purpose = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditions = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $selectedEquipmentIds = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $protocolScanFilename = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $protocolUploadedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $handedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $returnedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_GENERATED = 'generated';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Projekt',
        self::STATUS_GENERATED => 'Wygenerowane',
        self::STATUS_ACTIVE => 'Aktywne',
        self::STATUS_COMPLETED => 'Zakończone',
        self::STATUS_CANCELLED => 'Anulowane'
    ];

    public function __construct()
    {
        $this->status = self::STATUS_DRAFT;
        $this->selectedEquipmentIds = [];
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->generateTransferNumber();
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function generateTransferNumber(): void
    {
        $this->transferNumber = 'TR-' . date('Y') . '-' . uniqid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransferNumber(): ?string
    {
        return $this->transferNumber;
    }

    public function setTransferNumber(string $transferNumber): self
    {
        $this->transferNumber = $transferNumber;
        return $this;
    }

    public function getEquipment(): ?AsekuracyjnyEquipment
    {
        return $this->equipment;
    }

    public function setEquipment(?AsekuracyjnyEquipment $equipment): self
    {
        $this->equipment = $equipment;
        return $this;
    }

    public function getEquipmentSet(): ?AsekuracyjnyEquipmentSet
    {
        return $this->equipmentSet;
    }

    public function setEquipmentSet(?AsekuracyjnyEquipmentSet $equipmentSet): self
    {
        $this->equipmentSet = $equipmentSet;
        return $this;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusDisplayName(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTransferDate(): ?\DateTimeInterface
    {
        return $this->transferDate;
    }

    public function setTransferDate(\DateTimeInterface $transferDate): self
    {
        $this->transferDate = $transferDate;
        return $this;
    }

    public function getReturnDate(): ?\DateTimeInterface
    {
        return $this->returnDate;
    }

    public function setReturnDate(?\DateTimeInterface $returnDate): self
    {
        $this->returnDate = $returnDate;
        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(?string $purpose): self
    {
        $this->purpose = $purpose;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getConditions(): ?string
    {
        return $this->conditions;
    }

    public function setConditions(?string $conditions): self
    {
        $this->conditions = $conditions;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getSelectedEquipmentIds(): array
    {
        return $this->selectedEquipmentIds;
    }

    public function setSelectedEquipmentIds(array $selectedEquipmentIds): self
    {
        $this->selectedEquipmentIds = $selectedEquipmentIds;
        return $this;
    }

    public function getProtocolScanFilename(): ?string
    {
        return $this->protocolScanFilename;
    }

    public function setProtocolScanFilename(?string $protocolScanFilename): self
    {
        $this->protocolScanFilename = $protocolScanFilename;
        return $this;
    }

    public function getProtocolUploadedAt(): ?\DateTimeInterface
    {
        return $this->protocolUploadedAt;
    }

    public function setProtocolUploadedAt(?\DateTimeInterface $protocolUploadedAt): self
    {
        $this->protocolUploadedAt = $protocolUploadedAt;
        return $this;
    }

    public function getHandedBy(): ?User
    {
        return $this->handedBy;
    }

    public function setHandedBy(?User $handedBy): self
    {
        $this->handedBy = $handedBy;
        return $this;
    }

    public function getReturnedBy(): ?User
    {
        return $this->returnedBy;
    }

    public function setReturnedBy(?User $returnedBy): self
    {
        $this->returnedBy = $returnedBy;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeGenerated(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeActivated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_GENERATED, self::STATUS_ACTIVE]);
    }

    public function generateProtocol(): self
    {
        if (!$this->canBeGenerated()) {
            throw new \InvalidArgumentException('Protokół nie może być wygenerowany w aktualnym stanie');
        }

        $this->status = self::STATUS_GENERATED;
        return $this;
    }

    public function activate(): self
    {
        if (!$this->canBeActivated()) {
            throw new \InvalidArgumentException('Przekazanie nie może być aktywowane w aktualnym stanie');
        }

        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    public function complete(User $returnedBy): self
    {
        if (!$this->canBeCompleted()) {
            throw new \InvalidArgumentException('Przekazanie nie może być zakończone w aktualnym stanie');
        }

        $this->status = self::STATUS_COMPLETED;
        $this->returnDate = new \DateTime();
        $this->returnedBy = $returnedBy;
        return $this;
    }

    public function cancel(): self
    {
        if (!$this->canBeCancelled()) {
            throw new \InvalidArgumentException('Przekazanie nie może być anulowane w aktualnym stanie');
        }

        $this->status = self::STATUS_CANCELLED;
        return $this;
    }

    public function isForEquipmentSet(): bool
    {
        return $this->equipmentSet !== null;
    }

    public function isForSingleEquipment(): bool
    {
        return $this->equipment !== null;
    }

    public function hasSelectedEquipment(): bool
    {
        return !empty($this->selectedEquipmentIds);
    }

    public function hasProtocolScan(): bool
    {
        return $this->protocolScanFilename !== null;
    }

    public function getTransferSubject(): string
    {
        if ($this->isForSingleEquipment()) {
            return $this->equipment->getName();
        }

        if ($this->isForEquipmentSet()) {
            if ($this->hasSelectedEquipment()) {
                return $this->equipmentSet->getName() . ' (wybrane elementy)';
            }
            return $this->equipmentSet->getName() . ' (kompletny zestaw)';
        }

        return 'Nie określono';
    }

    public function getDurationInDays(): ?int
    {
        if (!$this->transferDate) {
            return null;
        }

        $endDate = $this->returnDate ?? new \DateTime();
        return $this->transferDate->diff($endDate)->days;
    }

    public function isOverdue(): bool
    {
        if (!$this->returnDate || $this->isCompleted()) {
            return false;
        }

        return new \DateTime() > $this->returnDate;
    }

    public function uploadProtocolScan(string $filename): self
    {
        $this->protocolScanFilename = $filename;
        $this->protocolUploadedAt = new \DateTime();
        
        if ($this->isGenerated()) {
            $this->activate();
        }
        
        return $this;
    }

    public function __toString(): string
    {
        return $this->transferNumber ?? '';
    }
}