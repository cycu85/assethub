<?php

namespace App\AsekuracyjnySPM\Entity;

use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentSetRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AsekuracyjnyEquipmentSetRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'asekuracyjny_equipment_set')]
class AsekuracyjnyEquipmentSet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Nazwa zestawu jest wymagana')]
    #[Assert\Length(max: 255, maxMessage: 'Nazwa nie może być dłuższa niż {{ limit }} znaków')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $setType = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $assignedTo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $assignedDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextReviewDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Okres przeglądu musi być liczbą dodatnią')]
    private ?int $reviewIntervalMonths = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $customFields = [];

    #[ORM\ManyToMany(targetEntity: AsekuracyjnyEquipment::class, inversedBy: 'equipmentSets')]
    #[ORM\JoinTable(name: 'asekuracyjny_equipment_set_items')]
    private Collection $equipment;

    #[ORM\OneToMany(mappedBy: 'equipmentSet', targetEntity: AsekuracyjnyReview::class, cascade: ['persist'])]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'equipmentSet', targetEntity: AsekuracyjnyTransfer::class, cascade: ['persist'])]
    private Collection $transfers;

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
    public const STATUS_INCOMPLETE = 'incomplete';
    public const STATUS_RETIRED = 'retired';

    public const STATUSES = [
        self::STATUS_AVAILABLE => 'Dostępny',
        self::STATUS_ASSIGNED => 'Przypisany',
        self::STATUS_IN_REVIEW => 'Na przeglądzie',
        self::STATUS_MAINTENANCE => 'W serwisie',
        self::STATUS_INCOMPLETE => 'Niekompletny',
        self::STATUS_RETIRED => 'Wycofany'
    ];

    public function __construct()
    {
        $this->equipment = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->transfers = new ArrayCollection();
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

    public function getSetType(): ?string
    {
        return $this->setType;
    }

    public function setSetType(?string $setType): self
    {
        $this->setType = $setType;
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
     * @return Collection<int, AsekuracyjnyEquipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function addEquipment(AsekuracyjnyEquipment $equipment): self
    {
        if (!$this->equipment->contains($equipment)) {
            $this->equipment->add($equipment);
        }

        return $this;
    }

    public function removeEquipment(AsekuracyjnyEquipment $equipment): self
    {
        $this->equipment->removeElement($equipment);
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
            $review->setEquipmentSet($this);
        }

        return $this;
    }

    public function removeReview(AsekuracyjnyReview $review): self
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getEquipmentSet() === $this) {
                $review->setEquipmentSet(null);
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
            $transfer->setEquipmentSet($this);
        }

        return $this;
    }

    public function removeTransfer(AsekuracyjnyTransfer $transfer): self
    {
        if ($this->transfers->removeElement($transfer)) {
            if ($transfer->getEquipmentSet() === $this) {
                $transfer->setEquipmentSet(null);
            }
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

    public function isIncomplete(): bool
    {
        return $this->status === self::STATUS_INCOMPLETE;
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

    public function getEquipmentCount(): int
    {
        return $this->equipment->count();
    }

    public function getAvailableEquipmentCount(): int
    {
        return $this->equipment->filter(function(AsekuracyjnyEquipment $equipment) {
            return $equipment->isAvailable();
        })->count();
    }

    public function hasAllEquipmentAvailable(): bool
    {
        return $this->getEquipmentCount() === $this->getAvailableEquipmentCount();
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

    public function calculateNextReviewDate(): ?\DateTimeInterface
    {
        if (!$this->reviewIntervalMonths) {
            return null;
        }

        $lastReview = $this->getLastReview();
        $baseDate = $lastReview?->getCompletedAt() ?? $this->createdAt;
        
        if (!$baseDate) {
            return null;
        }

        $nextDate = clone $baseDate;
        $nextDate->add(new \DateInterval('P' . $this->reviewIntervalMonths . 'M'));
        
        return $nextDate;
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}