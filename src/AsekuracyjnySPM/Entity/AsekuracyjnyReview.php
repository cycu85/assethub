<?php

namespace App\AsekuracyjnySPM\Entity;

use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AsekuracyjnyReviewRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'asekuracyjny_review')]
class AsekuracyjnyReview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $reviewNumber = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyEquipment::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AsekuracyjnyEquipment $equipment = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyEquipmentSet::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true)]
    private ?AsekuracyjnyEquipmentSet $equipmentSet = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reviewType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Data planowanego przeglądu jest wymagana')]
    private ?\DateTimeInterface $plannedDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $sentDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $completedDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $nextReviewDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reviewCompany = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certificateNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $result = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $findings = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $recommendations = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $selectedEquipmentIds = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $attachments = [];

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $preparedBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sentBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $completedBy = null;

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

    #[ORM\OneToMany(mappedBy: 'review', targetEntity: AsekuracyjnyReviewEquipment::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $reviewEquipments;

    public const STATUS_PREPARATION = 'preparation';
    public const STATUS_SENT = 'sent';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PREPARATION => 'Przygotowanie',
        self::STATUS_SENT => 'Wysłane na przegląd',
        self::STATUS_COMPLETED => 'Zakończone',
        self::STATUS_CANCELLED => 'Anulowane'
    ];

    public const RESULT_PASSED = 'passed';
    public const RESULT_FAILED = 'failed';
    public const RESULT_CONDITIONALLY_PASSED = 'conditionally_passed';
    public const RESULT_NOT_APPLICABLE = 'not_applicable';

    public const RESULTS = [
        self::RESULT_PASSED => 'Pozytywny',
        self::RESULT_FAILED => 'Negatywny',
        self::RESULT_CONDITIONALLY_PASSED => 'Warunkowo pozytywny',
        self::RESULT_NOT_APPLICABLE => 'Nie dotyczy'
    ];

    public const TYPE_PERIODIC = 'periodic';
    public const TYPE_DAMAGE_CONTROL = 'damage_control';
    public const TYPE_POST_REPAIR = 'post_repair';
    public const TYPE_INITIAL = 'initial';

    public const TYPES = [
        self::TYPE_PERIODIC => 'Okresowy',
        self::TYPE_DAMAGE_CONTROL => 'Kontrola po uszkodzeniu',
        self::TYPE_POST_REPAIR => 'Po naprawie',
        self::TYPE_INITIAL => 'Początkowy'
    ];

    public function __construct()
    {
        $this->status = self::STATUS_PREPARATION;
        $this->selectedEquipmentIds = [];
        $this->attachments = [];
        $this->reviewEquipments = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->generateReviewNumber();
    }

    #[ORM\PreUpdate]
    public function setUpdatedValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function generateReviewNumber(): void
    {
        $this->reviewNumber = 'REV-' . date('Y') . '-' . uniqid();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReviewNumber(): ?string
    {
        return $this->reviewNumber;
    }

    public function setReviewNumber(string $reviewNumber): self
    {
        $this->reviewNumber = $reviewNumber;
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

    public function getReviewType(): ?string
    {
        return $this->reviewType;
    }

    public function setReviewType(?string $reviewType): self
    {
        $this->reviewType = $reviewType;
        return $this;
    }

    public function getReviewTypeDisplayName(): string
    {
        return self::TYPES[$this->reviewType] ?? $this->reviewType;
    }

    public function getPlannedDate(): ?\DateTimeInterface
    {
        return $this->plannedDate;
    }

    public function setPlannedDate(\DateTimeInterface $plannedDate): self
    {
        $this->plannedDate = $plannedDate;
        return $this;
    }

    public function getSentDate(): ?\DateTimeInterface
    {
        return $this->sentDate;
    }

    public function setSentDate(?\DateTimeInterface $sentDate): self
    {
        $this->sentDate = $sentDate;
        return $this;
    }

    public function getCompletedDate(): ?\DateTimeInterface
    {
        return $this->completedDate;
    }

    public function setCompletedDate(?\DateTimeInterface $completedDate): self
    {
        $this->completedDate = $completedDate;
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

    public function getReviewCompany(): ?string
    {
        return $this->reviewCompany;
    }

    public function setReviewCompany(?string $reviewCompany): self
    {
        $this->reviewCompany = $reviewCompany;
        return $this;
    }

    public function getCertificateNumber(): ?string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(?string $certificateNumber): self
    {
        $this->certificateNumber = $certificateNumber;
        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;
        return $this;
    }

    public function getResultDisplayName(): string
    {
        return self::RESULTS[$this->result] ?? $this->result ?? '';
    }

    public function getFindings(): ?string
    {
        return $this->findings;
    }

    public function setFindings(?string $findings): self
    {
        $this->findings = $findings;
        return $this;
    }

    public function getRecommendations(): ?string
    {
        return $this->recommendations;
    }

    public function setRecommendations(?string $recommendations): self
    {
        $this->recommendations = $recommendations;
        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): self
    {
        $this->cost = $cost;
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

    public function getSelectedEquipmentIds(): array
    {
        return $this->selectedEquipmentIds;
    }

    public function setSelectedEquipmentIds(array $selectedEquipmentIds): self
    {
        $this->selectedEquipmentIds = $selectedEquipmentIds;
        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function setAttachments(array $attachments): self
    {
        $this->attachments = $attachments;
        return $this;
    }

    public function addAttachment(string $filename, string $originalName): self
    {
        $this->attachments[] = [
            'filename' => $filename,
            'originalName' => $originalName,
            'uploadedAt' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        return $this;
    }

    public function removeAttachment(string $filename): self
    {
        $this->attachments = array_filter($this->attachments, function($attachment) use ($filename) {
            return $attachment['filename'] !== $filename;
        });
        return $this;
    }

    public function getPreparedBy(): ?User
    {
        return $this->preparedBy;
    }

    public function setPreparedBy(?User $preparedBy): self
    {
        $this->preparedBy = $preparedBy;
        return $this;
    }

    public function getSentBy(): ?User
    {
        return $this->sentBy;
    }

    public function setSentBy(?User $sentBy): self
    {
        $this->sentBy = $sentBy;
        return $this;
    }

    public function getCompletedBy(): ?User
    {
        return $this->completedBy;
    }

    public function setCompletedBy(?User $completedBy): self
    {
        $this->completedBy = $completedBy;
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
        
        // Auto-set preparedBy if not already set
        if ($createdBy && !$this->preparedBy) {
            $this->preparedBy = $createdBy;
        }
        
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

    public function isInPreparation(): bool
    {
        return $this->status === self::STATUS_PREPARATION;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeSent(): bool
    {
        return $this->status === self::STATUS_PREPARATION;
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PREPARATION, self::STATUS_SENT]);
    }

    public function sendToReview(User $sentBy): self
    {
        if (!$this->canBeSent()) {
            throw new \InvalidArgumentException('Przegląd nie może być wysłany w aktualnym stanie');
        }

        $this->status = self::STATUS_SENT;
        $this->sentDate = new \DateTime();
        $this->sentBy = $sentBy;

        return $this;
    }

    public function completeReview(User $completedBy, ?string $result = null): self
    {
        if (!$this->canBeCompleted()) {
            throw new \InvalidArgumentException('Przegląd nie może być zakończony w aktualnym stanie');
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completedDate = new \DateTime();
        $this->completedBy = $completedBy;
        
        if ($result) {
            $this->result = $result;
        }

        return $this;
    }

    public function cancel(): self
    {
        if (!$this->canBeCancelled()) {
            throw new \InvalidArgumentException('Przegląd nie może być anulowany w aktualnym stanie');
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

    public function getReviewSubject(): string
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
        if (!$this->sentDate) {
            return null;
        }

        $endDate = $this->completedDate ?? new \DateTime();
        return $this->sentDate->diff($endDate)->days;
    }

    /**
     * @return Collection<int, AsekuracyjnyReviewEquipment>
     */
    public function getReviewEquipments(): Collection
    {
        return $this->reviewEquipments;
    }

    public function addReviewEquipment(AsekuracyjnyReviewEquipment $reviewEquipment): self
    {
        if (!$this->reviewEquipments->contains($reviewEquipment)) {
            $this->reviewEquipments->add($reviewEquipment);
            $reviewEquipment->setReview($this);
        }

        return $this;
    }

    public function removeReviewEquipment(AsekuracyjnyReviewEquipment $reviewEquipment): self
    {
        if ($this->reviewEquipments->removeElement($reviewEquipment)) {
            if ($reviewEquipment->getReview() === $this) {
                $reviewEquipment->setReview(null);
            }
        }

        return $this;
    }

    /**
     * Get all equipments that are part of this review (both individual and from sets)
     */
    public function getReviewedEquipments(): Collection
    {
        return $this->reviewEquipments->map(function(AsekuracyjnyReviewEquipment $reviewEquipment) {
            return $reviewEquipment->getEquipment();
        })->filter(function($equipment) {
            return $equipment !== null;
        });
    }

    /**
     * Check if specific equipment is part of this review
     */
    public function hasEquipmentInReview(AsekuracyjnyEquipment $equipment): bool
    {
        foreach ($this->reviewEquipments as $reviewEquipment) {
            if ($reviewEquipment->getEquipment() === $equipment) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get count of equipments in this review
     */
    public function getReviewedEquipmentsCount(): int
    {
        return $this->reviewEquipments->count();
    }

    /**
     * Get equipments that were reviewed as part of a set
     */
    public function getSetReviewedEquipments(): Collection
    {
        return $this->reviewEquipments->filter(function(AsekuracyjnyReviewEquipment $reviewEquipment) {
            return $reviewEquipment->wasSetReview();
        });
    }

    /**
     * Get equipments that were reviewed individually
     */
    public function getIndividuallyReviewedEquipments(): Collection
    {
        return $this->reviewEquipments->filter(function(AsekuracyjnyReviewEquipment $reviewEquipment) {
            return $reviewEquipment->wasIndividualReview();
        });
    }

    public function __toString(): string
    {
        return $this->reviewNumber ?? '';
    }
}