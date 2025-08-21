<?php

namespace App\AsekuracyjnySPM\Entity;

use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewEquipmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AsekuracyjnyReviewEquipmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'asekuracyjny_review_equipment')]
#[ORM\UniqueConstraint(name: 'unique_review_equipment', columns: ['review_id', 'equipment_id'])]
class AsekuracyjnyReviewEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyReview::class, inversedBy: 'reviewEquipments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AsekuracyjnyReview $review = null;

    #[ORM\ManyToOne(targetEntity: AsekuracyjnyEquipment::class, inversedBy: 'reviewEquipments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AsekuracyjnyEquipment $equipment = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $equipmentStatusAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $equipmentNameAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $equipmentInventoryNumberAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $equipmentTypeAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $equipmentManufacturerAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $equipmentModelAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $equipmentSerialNumberAtReview = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $equipmentNextReviewDateAtReview = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $individualResult = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $individualFindings = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $individualRecommendations = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?string $individualNextReviewDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $wasInSetAtReview = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $setNameAtReview = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public const RESULT_PASSED = 'passed';
    public const RESULT_FAILED = 'failed';
    public const RESULT_CONDITIONALLY_PASSED = 'conditionally_passed';
    public const RESULT_NOT_APPLICABLE = 'not_applicable';
    public const RESULT_INHERITED = 'inherited';

    public const RESULTS = [
        self::RESULT_PASSED => 'Pozytywny',
        self::RESULT_FAILED => 'Negatywny',
        self::RESULT_CONDITIONALLY_PASSED => 'Warunkowo pozytywny',
        self::RESULT_NOT_APPLICABLE => 'Nie dotyczy',
        self::RESULT_INHERITED => 'Zgodnie z zestawem'
    ];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->wasInSetAtReview = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReview(): ?AsekuracyjnyReview
    {
        return $this->review;
    }

    public function setReview(?AsekuracyjnyReview $review): self
    {
        $this->review = $review;
        return $this;
    }

    public function getEquipment(): ?AsekuracyjnyEquipment
    {
        return $this->equipment;
    }

    public function setEquipment(?AsekuracyjnyEquipment $equipment): self
    {
        $this->equipment = $equipment;
        
        // Auto-populate equipment data at review time
        if ($equipment) {
            $this->captureEquipmentSnapshot($equipment);
        }
        
        return $this;
    }

    public function getEquipmentStatusAtReview(): ?string
    {
        return $this->equipmentStatusAtReview;
    }

    public function setEquipmentStatusAtReview(?string $equipmentStatusAtReview): self
    {
        $this->equipmentStatusAtReview = $equipmentStatusAtReview;
        return $this;
    }

    public function getEquipmentNameAtReview(): ?string
    {
        return $this->equipmentNameAtReview;
    }

    public function setEquipmentNameAtReview(?string $equipmentNameAtReview): self
    {
        $this->equipmentNameAtReview = $equipmentNameAtReview;
        return $this;
    }

    public function getEquipmentInventoryNumberAtReview(): ?string
    {
        return $this->equipmentInventoryNumberAtReview;
    }

    public function setEquipmentInventoryNumberAtReview(?string $equipmentInventoryNumberAtReview): self
    {
        $this->equipmentInventoryNumberAtReview = $equipmentInventoryNumberAtReview;
        return $this;
    }

    public function getEquipmentTypeAtReview(): ?string
    {
        return $this->equipmentTypeAtReview;
    }

    public function setEquipmentTypeAtReview(?string $equipmentTypeAtReview): self
    {
        $this->equipmentTypeAtReview = $equipmentTypeAtReview;
        return $this;
    }

    public function getEquipmentManufacturerAtReview(): ?string
    {
        return $this->equipmentManufacturerAtReview;
    }

    public function setEquipmentManufacturerAtReview(?string $equipmentManufacturerAtReview): self
    {
        $this->equipmentManufacturerAtReview = $equipmentManufacturerAtReview;
        return $this;
    }

    public function getEquipmentModelAtReview(): ?string
    {
        return $this->equipmentModelAtReview;
    }

    public function setEquipmentModelAtReview(?string $equipmentModelAtReview): self
    {
        $this->equipmentModelAtReview = $equipmentModelAtReview;
        return $this;
    }

    public function getEquipmentSerialNumberAtReview(): ?string
    {
        return $this->equipmentSerialNumberAtReview;
    }

    public function setEquipmentSerialNumberAtReview(?string $equipmentSerialNumberAtReview): self
    {
        $this->equipmentSerialNumberAtReview = $equipmentSerialNumberAtReview;
        return $this;
    }

    public function getEquipmentNextReviewDateAtReview(): ?\DateTimeInterface
    {
        return $this->equipmentNextReviewDateAtReview;
    }

    public function setEquipmentNextReviewDateAtReview(?\DateTimeInterface $equipmentNextReviewDateAtReview): self
    {
        $this->equipmentNextReviewDateAtReview = $equipmentNextReviewDateAtReview;
        return $this;
    }

    public function getIndividualResult(): ?string
    {
        return $this->individualResult;
    }

    public function setIndividualResult(?string $individualResult): self
    {
        $this->individualResult = $individualResult;
        return $this;
    }

    public function getIndividualResultDisplayName(): string
    {
        return self::RESULTS[$this->individualResult] ?? $this->individualResult ?? '';
    }

    public function getIndividualFindings(): ?string
    {
        return $this->individualFindings;
    }

    public function setIndividualFindings(?string $individualFindings): self
    {
        $this->individualFindings = $individualFindings;
        return $this;
    }

    public function getIndividualRecommendations(): ?string
    {
        return $this->individualRecommendations;
    }

    public function setIndividualRecommendations(?string $individualRecommendations): self
    {
        $this->individualRecommendations = $individualRecommendations;
        return $this;
    }

    public function getIndividualNextReviewDate(): ?string
    {
        return $this->individualNextReviewDate;
    }

    public function setIndividualNextReviewDate(?string $individualNextReviewDate): self
    {
        $this->individualNextReviewDate = $individualNextReviewDate;
        return $this;
    }

    public function isWasInSetAtReview(): bool
    {
        return $this->wasInSetAtReview;
    }

    public function setWasInSetAtReview(bool $wasInSetAtReview): self
    {
        $this->wasInSetAtReview = $wasInSetAtReview;
        return $this;
    }

    public function getSetNameAtReview(): ?string
    {
        return $this->setNameAtReview;
    }

    public function setSetNameAtReview(?string $setNameAtReview): self
    {
        $this->setNameAtReview = $setNameAtReview;
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

    /**
     * Capture equipment snapshot at review time - preserves data even if equipment is later modified/deleted
     */
    public function captureEquipmentSnapshot(AsekuracyjnyEquipment $equipment): self
    {
        $this->equipmentNameAtReview = $equipment->getName();
        $this->equipmentInventoryNumberAtReview = $equipment->getInventoryNumber();
        $this->equipmentStatusAtReview = $equipment->getStatus();
        $this->equipmentTypeAtReview = $equipment->getEquipmentType();
        $this->equipmentManufacturerAtReview = $equipment->getManufacturer();
        $this->equipmentModelAtReview = $equipment->getModel();
        $this->equipmentSerialNumberAtReview = $equipment->getSerialNumber();
        $this->equipmentNextReviewDateAtReview = $equipment->getNextReviewDate();

        return $this;
    }

    /**
     * Capture set context at review time
     */
    public function captureSetContext(AsekuracyjnyEquipmentSet $equipmentSet): self
    {
        $this->wasInSetAtReview = true;
        $this->setNameAtReview = $equipmentSet->getName();
        return $this;
    }

    /**
     * Get effective result (individual or inherited from review)
     */
    public function getEffectiveResult(): ?string
    {
        if ($this->individualResult && $this->individualResult !== self::RESULT_INHERITED) {
            return $this->individualResult;
        }

        return $this->review?->getResult();
    }

    /**
     * Get effective result display name
     */
    public function getEffectiveResultDisplayName(): string
    {
        $result = $this->getEffectiveResult();
        return AsekuracyjnyReview::RESULTS[$result] ?? $result ?? '';
    }

    /**
     * Get equipment name (current or snapshot)
     */
    public function getEquipmentDisplayName(): string
    {
        return $this->equipment?->getName() ?? $this->equipmentNameAtReview ?? 'Nieznany sprzęt';
    }

    /**
     * Get equipment inventory number (current or snapshot)
     */
    public function getEquipmentDisplayInventoryNumber(): string
    {
        return $this->equipment?->getInventoryNumber() ?? $this->equipmentInventoryNumberAtReview ?? '';
    }

    /**
     * Check if equipment still exists
     */
    public function hasEquipmentStillExists(): bool
    {
        return $this->equipment !== null;
    }

    /**
     * Check if this was an individual equipment review (not part of set)
     */
    public function wasIndividualReview(): bool
    {
        return !$this->wasInSetAtReview;
    }

    /**
     * Check if this was part of a set review
     */
    public function wasSetReview(): bool
    {
        return $this->wasInSetAtReview;
    }

    public function __toString(): string
    {
        return sprintf('%s - %s', 
            $this->review?->getReviewNumber() ?? 'Przegląd',
            $this->getEquipmentDisplayName()
        );
    }
}