<?php

namespace App\AsekuracyjnySPM\Entity;

use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AsekuracyjnyEquipmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'asekuracyjny_equipment')]
class AsekuracyjnyEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Numer inwentarzowy jest wymagany')]
    #[Assert\Length(max: 100, maxMessage: 'Numer inwentarzowy nie może być dłuższy niż {{ limit }} znaków')]
    private ?string $inventoryNumber = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Nazwa sprzętu jest wymagana')]
    #[Assert\Length(max: 255, maxMessage: 'Nazwa nie może być dłuższa niż {{ limit }} znaków')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: false)]
    #[Assert\NotBlank(message: 'Typ sprzętu jest wymagany')]
    private ?string $equipmentType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $manufacturer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serialNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $manufacturingDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $purchaseDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $purchasePrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $supplier = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $warrantyExpiry = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\NotNull(message: 'Data następnego przeglądu jest wymagana')]
    private ?\DateTimeInterface $nextReviewDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Okres przeglądu musi być liczbą dodatnią')]
    private ?int $reviewIntervalMonths = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $assignedDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $customFields = [];

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: AsekuracyjnyReview::class, cascade: ['persist'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'equipment', targetEntity: AsekuracyjnyTransfer::class, cascade: ['persist'])]
    private Collection $transfers;

    #[ORM\ManyToMany(targetEntity: AsekuracyjnyEquipmentSet::class, mappedBy: 'equipment')]
    private Collection $equipmentSets;

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

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_ASSIGNED = 'assigned';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_RETIRED = 'retired';

    public const STATUSES = [
        self::STATUS_AVAILABLE => 'Dostępny',
        self::STATUS_ASSIGNED => 'Przypisany',
        self::STATUS_IN_REVIEW => 'Na przeglądzie',
        self::STATUS_MAINTENANCE => 'W serwisie',
        self::STATUS_DAMAGED => 'Uszkodzony',
        self::STATUS_RETIRED => 'Wycofany'
    ];

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->transfers = new ArrayCollection();
        $this->equipmentSets = new ArrayCollection();
        $this->status = self::STATUS_AVAILABLE;
        $this->customFields = [];
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInventoryNumber(): ?string
    {
        return $this->inventoryNumber;
    }

    public function setInventoryNumber(string $inventoryNumber): self
    {
        $this->inventoryNumber = $inventoryNumber;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEquipmentType(): ?string
    {
        return $this->equipmentType;
    }

    public function setEquipmentType(string $equipmentType): self
    {
        $this->equipmentType = $equipmentType;
        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): self
    {
        $this->manufacturer = $manufacturer;
        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): self
    {
        $this->serialNumber = $serialNumber;
        return $this;
    }

    public function getManufacturingDate(): ?\DateTimeInterface
    {
        return $this->manufacturingDate;
    }

    public function setManufacturingDate(?\DateTimeInterface $manufacturingDate): self
    {
        $this->manufacturingDate = $manufacturingDate;
        return $this;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchaseDate;
    }

    public function setPurchaseDate(?\DateTimeInterface $purchaseDate): self
    {
        $this->purchaseDate = $purchaseDate;
        return $this;
    }

    public function getPurchasePrice(): ?string
    {
        return $this->purchasePrice;
    }

    public function setPurchasePrice(?string $purchasePrice): self
    {
        $this->purchasePrice = $purchasePrice;
        return $this;
    }

    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    public function setSupplier(?string $supplier): self
    {
        $this->supplier = $supplier;
        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        return $this;
    }

    public function getWarrantyExpiry(): ?\DateTimeInterface
    {
        return $this->warrantyExpiry;
    }

    public function setWarrantyExpiry(?\DateTimeInterface $warrantyExpiry): self
    {
        $this->warrantyExpiry = $warrantyExpiry;
        return $this;
    }

    public function getNextReviewDate(): ?\DateTimeInterface
    {
        return $this->nextReviewDate;
    }

    public function setNextReviewDate(?\DateTimeInterface $nextReviewDate): self
    {
        $this->nextReviewDate = $nextReviewDate;
        return $this;
    }

    public function getReviewIntervalMonths(): ?int
    {
        return $this->reviewIntervalMonths;
    }

    public function setReviewIntervalMonths(?int $reviewIntervalMonths): self
    {
        $this->reviewIntervalMonths = $reviewIntervalMonths;
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

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        $this->assignedDate = $assignedTo ? new \DateTime() : null;
        return $this;
    }

    public function getAssignedDate(): ?\DateTimeInterface
    {
        return $this->assignedDate;
    }

    public function setAssignedDate(?\DateTimeInterface $assignedDate): self
    {
        $this->assignedDate = $assignedDate;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCustomFields(): array
    {
        return $this->customFields;
    }

    public function setCustomFields(array $customFields): self
    {
        $this->customFields = $customFields;
        return $this;
    }

    /**
     * @return Collection<int, AsekuracyjnyReview>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(AsekuracyjnyReview $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setEquipment($this);
        }

        return $this;
    }

    public function removeReview(AsekuracyjnyReview $review): self
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getEquipment() === $this) {
                $review->setEquipment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AsekuracyjnyTransfer>
     */
    public function getTransfers(): Collection
    {
        return $this->transfers;
    }

    public function addTransfer(AsekuracyjnyTransfer $transfer): self
    {
        if (!$this->transfers->contains($transfer)) {
            $this->transfers->add($transfer);
            $transfer->setEquipment($this);
        }

        return $this;
    }

    public function removeTransfer(AsekuracyjnyTransfer $transfer): self
    {
        if ($this->transfers->removeElement($transfer)) {
            if ($transfer->getEquipment() === $this) {
                $transfer->setEquipment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AsekuracyjnyEquipmentSet>
     */
    public function getEquipmentSets(): Collection
    {
        return $this->equipmentSets;
    }

    public function addEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): self
    {
        if (!$this->equipmentSets->contains($equipmentSet)) {
            $this->equipmentSets->add($equipmentSet);
            $equipmentSet->addEquipment($this);
        }

        return $this;
    }

    public function removeEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): self
    {
        if ($this->equipmentSets->removeElement($equipmentSet)) {
            $equipmentSet->removeEquipment($this);
        }

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

    public function isAssigned(): bool
    {
        return $this->assignedTo !== null;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isInReview(): bool
    {
        return $this->status === self::STATUS_IN_REVIEW;
    }

    public function isDamaged(): bool
    {
        return $this->status === self::STATUS_DAMAGED;
    }

    public function needsReview(): bool
    {
        if (!$this->nextReviewDate) {
            return false;
        }

        $now = new \DateTime();
        $warningDate = clone $this->nextReviewDate;
        $warningDate->modify('-30 days');

        return $now >= $warningDate;
    }

    public function isReviewOverdue(): bool
    {
        if (!$this->nextReviewDate) {
            return false;
        }

        return new \DateTime() > $this->nextReviewDate;
    }

    public function getLastReview(): ?AsekuracyjnyReview
    {
        $reviews = $this->reviews->toArray();
        if (empty($reviews)) {
            return null;
        }

        usort($reviews, function($a, $b) {
            return $b->getCompletedAt() <=> $a->getCompletedAt();
        });

        return $reviews[0] ?? null;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}