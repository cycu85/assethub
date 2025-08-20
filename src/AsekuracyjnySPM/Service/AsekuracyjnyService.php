<?php

namespace App\AsekuracyjnySPM\Service;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentSetRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewRepository;
use App\Entity\User;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use App\Exception\BusinessLogicException;

class AsekuracyjnyService
{
    public function __construct(
        private AsekuracyjnyEquipmentRepository $equipmentRepository,
        private AsekuracyjnyEquipmentSetRepository $equipmentSetRepository,
        private AsekuracyjnyReviewRepository $reviewRepository,
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ValidatorInterface $validator
    ) {}

    // === EQUIPMENT MANAGEMENT ===

    public function createEquipment(array $data, User $user): AsekuracyjnyEquipment
    {
        $this->validateEquipmentData($data);

        $equipment = new AsekuracyjnyEquipment();
        $this->populateEquipmentFromArray($equipment, $data);
        $equipment->setCreatedBy($user);

        $violations = $this->validator->validate($equipment);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        if ($this->equipmentRepository->findByInventoryNumber($equipment->getInventoryNumber())) {
            throw new BusinessLogicException('Sprzęt o takim numerze inwentarzowym już istnieje');
        }

        $this->entityManager->persist($equipment);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipment', $equipment->getId(), 'CREATE', $data);

        $this->logger->info('Utworzono sprzęt asekuracyjny', [
            'equipment_id' => $equipment->getId(),
            'inventory_number' => $equipment->getInventoryNumber(),
            'user' => $user->getUsername()
        ]);

        return $equipment;
    }

    public function updateEquipment(AsekuracyjnyEquipment $equipment, array $data, User $user): AsekuracyjnyEquipment
    {
        $this->validateEquipmentData($data, $equipment->getId());

        $oldData = $this->getEquipmentDataArray($equipment);
        $this->populateEquipmentFromArray($equipment, $data);
        $equipment->setUpdatedBy($user);

        $violations = $this->validator->validate($equipment);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipment', $equipment->getId(), 'UPDATE', [
            'old_data' => $oldData,
            'new_data' => $data
        ]);

        $this->logger->info('Zaktualizowano sprzęt asekuracyjny', [
            'equipment_id' => $equipment->getId(),
            'user' => $user->getUsername()
        ]);

        return $equipment;
    }

    public function deleteEquipment(AsekuracyjnyEquipment $equipment, User $user): void
    {
        if ($equipment->isAssigned()) {
            throw new BusinessLogicException('Nie można usunąć przypisanego sprzętu');
        }

        if (!$equipment->getEquipmentSets()->isEmpty()) {
            throw new BusinessLogicException('Nie można usunąć sprzętu będącego częścią zestawu');
        }

        if (!$equipment->getReviews()->isEmpty()) {
            throw new BusinessLogicException('Nie można usunąć sprzętu z historią przeglądów');
        }

        $equipmentData = $this->getEquipmentDataArray($equipment);
        $equipmentId = $equipment->getId();

        $this->entityManager->remove($equipment);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipment', $equipmentId, 'DELETE', $equipmentData);

        $this->logger->info('Usunięto sprzęt asekuracyjny', [
            'equipment_id' => $equipmentId,
            'user' => $user->getUsername()
        ]);
    }

    public function assignEquipment(AsekuracyjnyEquipment $equipment, User $assignee, User $assignedBy, ?string $notes = null): AsekuracyjnyEquipment
    {
        if (!$equipment->isAvailable()) {
            throw new BusinessLogicException('Sprzęt nie jest dostępny do przypisania');
        }

        $equipment->setAssignedTo($assignee);
        $equipment->setStatus(AsekuracyjnyEquipment::STATUS_ASSIGNED);
        $equipment->setUpdatedBy($assignedBy);
        
        if ($notes) {
            $equipment->setNotes($notes);
        }

        $this->entityManager->flush();

        $this->auditService->logUserAction($assignedBy, 'equipment_assigned', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'assigned_to' => $assignee->getUsername(),
            'notes' => $notes
        ]);

        $this->logger->info('Przypisano sprzęt asekuracyjny', [
            'equipment_id' => $equipment->getId(),
            'assigned_to' => $assignee->getUsername(),
            'assigned_by' => $assignedBy->getUsername()
        ]);

        return $equipment;
    }

    public function unassignEquipment(AsekuracyjnyEquipment $equipment, User $user): AsekuracyjnyEquipment
    {
        if (!$equipment->isAssigned()) {
            throw new BusinessLogicException('Sprzęt nie jest przypisany');
        }

        $previousAssignee = $equipment->getAssignedTo();
        $equipment->setAssignedTo(null);
        $equipment->setStatus(AsekuracyjnyEquipment::STATUS_AVAILABLE);
        $equipment->setUpdatedBy($user);

        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'equipment_unassigned', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'previous_assignee' => $previousAssignee?->getUsername()
        ]);

        $this->logger->info('Cofnięto przypisanie sprzętu asekuracyjnego', [
            'equipment_id' => $equipment->getId(),
            'previous_assignee' => $previousAssignee?->getUsername(),
            'unassigned_by' => $user->getUsername()
        ]);

        return $equipment;
    }

    // === EQUIPMENT SET MANAGEMENT ===

    public function createEquipmentSet(array $data, User $user): AsekuracyjnyEquipmentSet
    {
        $this->validateEquipmentSetData($data);

        $equipmentSet = new AsekuracyjnyEquipmentSet();
        $this->populateEquipmentSetFromArray($equipmentSet, $data);
        $equipmentSet->setCreatedBy($user);

        $violations = $this->validator->validate($equipmentSet);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($equipmentSet);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipmentSet', $equipmentSet->getId(), 'CREATE', $data);

        $this->logger->info('Utworzono zestaw asekuracyjny', [
            'set_id' => $equipmentSet->getId(),
            'set_name' => $equipmentSet->getName(),
            'user' => $user->getUsername()
        ]);

        return $equipmentSet;
    }

    public function updateEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet, array $data, User $user): AsekuracyjnyEquipmentSet
    {
        $this->validateEquipmentSetData($data, $equipmentSet->getId());

        $oldData = $this->getEquipmentSetDataArray($equipmentSet);
        $this->populateEquipmentSetFromArray($equipmentSet, $data);
        $equipmentSet->setUpdatedBy($user);

        $violations = $this->validator->validate($equipmentSet);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipmentSet', $equipmentSet->getId(), 'UPDATE', [
            'old_data' => $oldData,
            'new_data' => $data
        ]);

        $this->logger->info('Zaktualizowano zestaw asekuracyjny', [
            'set_id' => $equipmentSet->getId(),
            'user' => $user->getUsername()
        ]);

        return $equipmentSet;
    }

    public function deleteEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet, User $user): void
    {
        if ($equipmentSet->isAssigned()) {
            throw new BusinessLogicException('Nie można usunąć przypisanego zestawu');
        }

        if (!$equipmentSet->getReviews()->isEmpty()) {
            throw new BusinessLogicException('Nie można usunąć zestawu z historią przeglądów');
        }

        $setData = $this->getEquipmentSetDataArray($equipmentSet);
        $setId = $equipmentSet->getId();

        $this->entityManager->remove($equipmentSet);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyEquipmentSet', $setId, 'DELETE', $setData);

        $this->logger->info('Usunięto zestaw asekuracyjny', [
            'set_id' => $setId,
            'user' => $user->getUsername()
        ]);
    }

    public function addEquipmentToSet(AsekuracyjnyEquipmentSet $equipmentSet, AsekuracyjnyEquipment $equipment, User $user): AsekuracyjnyEquipmentSet
    {
        if ($equipmentSet->getEquipment()->contains($equipment)) {
            throw new BusinessLogicException('Sprzęt już należy do tego zestawu');
        }

        $equipmentSet->addEquipment($equipment);
        $equipmentSet->setUpdatedBy($user);

        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'equipment_added_to_set', [
            'set_id' => $equipmentSet->getId(),
            'set_name' => $equipmentSet->getName(),
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName()
        ]);

        return $equipmentSet;
    }

    public function removeEquipmentFromSet(AsekuracyjnyEquipmentSet $equipmentSet, AsekuracyjnyEquipment $equipment, User $user): AsekuracyjnyEquipmentSet
    {
        if (!$equipmentSet->getEquipment()->contains($equipment)) {
            throw new BusinessLogicException('Sprzęt nie należy do tego zestawu');
        }

        $equipmentSet->removeEquipment($equipment);
        $equipmentSet->setUpdatedBy($user);

        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'equipment_removed_from_set', [
            'set_id' => $equipmentSet->getId(),
            'set_name' => $equipmentSet->getName(),
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName()
        ]);

        return $equipmentSet;
    }

    // === QUERY METHODS ===

    public function getEquipment(int $id): ?AsekuracyjnyEquipment
    {
        return $this->equipmentRepository->find($id);
    }

    public function getEquipmentSet(int $id): ?AsekuracyjnyEquipmentSet
    {
        return $this->equipmentSetRepository->find($id);
    }

    public function getEquipmentWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        return $this->equipmentRepository->findWithPagination($page, $limit, $filters);
    }

    public function getEquipmentSetsWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        return $this->equipmentSetRepository->findWithPagination($page, $limit, $filters);
    }

    public function searchEquipment(string $query, int $limit = 10): array
    {
        return $this->equipmentRepository->search($query, $limit);
    }

    public function searchEquipmentSets(string $query, int $limit = 10): array
    {
        return $this->equipmentSetRepository->search($query, $limit);
    }

    public function getUserAssignedEquipment(User $user): array
    {
        return [
            'equipment' => $this->equipmentRepository->findAssignedToUser($user),
            'equipment_sets' => $this->equipmentSetRepository->findAssignedToUser($user)
        ];
    }

    public function getEquipmentStatistics(): array
    {
        return $this->equipmentRepository->getStatistics();
    }

    public function getEquipmentSetStatistics(): array
    {
        return $this->equipmentSetRepository->getStatistics();
    }

    public function getAvailableEquipment(): array
    {
        return $this->equipmentRepository->findAvailable();
    }

    public function getAvailableEquipmentForSet(): array
    {
        return $this->equipmentRepository->findAvailableForEquipmentSet();
    }

    public function getAvailableEquipmentSets(): array
    {
        return $this->equipmentSetRepository->findAvailable();
    }

    public function getEquipmentNeedingReview(): array
    {
        return [
            'equipment' => $this->equipmentRepository->findNeedingReview(),
            'equipment_sets' => $this->equipmentSetRepository->findNeedingReview()
        ];
    }

    public function getOverdueReviews(): array
    {
        return [
            'equipment' => $this->equipmentRepository->findOverdueReviews(),
            'equipment_sets' => $this->equipmentSetRepository->findOverdueReviews()
        ];
    }

    // === REPOSITORY GETTERS ===

    public function getEquipmentRepository(): AsekuracyjnyEquipmentRepository
    {
        return $this->equipmentRepository;
    }

    public function getEquipmentSetRepository(): AsekuracyjnyEquipmentSetRepository
    {
        return $this->equipmentSetRepository;
    }

    // === PRIVATE HELPER METHODS ===

    private function validateEquipmentData(array $data, ?int $excludeId = null): void
    {
        if (empty($data['name'])) {
            throw new ValidationException('Nazwa sprzętu jest wymagana');
        }

        if (empty($data['inventory_number'])) {
            throw new ValidationException('Numer inwentarzowy jest wymagany');
        }

        if (empty($data['equipment_type'])) {
            throw new ValidationException('Typ sprzętu jest wymagany');
        }
    }

    private function validateEquipmentSetData(array $data, ?int $excludeId = null): void
    {
        if (empty($data['name'])) {
            throw new ValidationException('Nazwa zestawu jest wymagana');
        }
    }

    private function populateEquipmentFromArray(AsekuracyjnyEquipment $equipment, array $data): void
    {
        if (isset($data['name'])) {
            $equipment->setName($data['name']);
        }
        if (isset($data['inventory_number'])) {
            $equipment->setInventoryNumber($data['inventory_number']);
        }
        if (isset($data['description'])) {
            $equipment->setDescription($data['description']);
        }
        if (isset($data['equipment_type'])) {
            $equipment->setEquipmentType($data['equipment_type']);
        }
        if (isset($data['manufacturer'])) {
            $equipment->setManufacturer($data['manufacturer']);
        }
        if (isset($data['model'])) {
            $equipment->setModel($data['model']);
        }
        if (isset($data['serial_number'])) {
            $equipment->setSerialNumber($data['serial_number']);
        }
        if (isset($data['manufacturing_date'])) {
            $equipment->setManufacturingDate($data['manufacturing_date']);
        }
        if (isset($data['purchase_date'])) {
            $equipment->setPurchaseDate($data['purchase_date']);
        }
        if (isset($data['purchase_price'])) {
            $equipment->setPurchasePrice($data['purchase_price']);
        }
        if (isset($data['supplier'])) {
            $equipment->setSupplier($data['supplier']);
        }
        if (isset($data['invoice_number'])) {
            $equipment->setInvoiceNumber($data['invoice_number']);
        }
        if (isset($data['warranty_expiry'])) {
            $equipment->setWarrantyExpiry($data['warranty_expiry']);
        }
        if (isset($data['next_review_date'])) {
            $equipment->setNextReviewDate($data['next_review_date']);
        }
        if (isset($data['review_interval_months'])) {
            $equipment->setReviewIntervalMonths($data['review_interval_months']);
        }
        if (isset($data['location'])) {
            $equipment->setLocation($data['location']);
        }
        if (isset($data['notes'])) {
            $equipment->setNotes($data['notes']);
        }
    }

    private function populateEquipmentSetFromArray(AsekuracyjnyEquipmentSet $equipmentSet, array $data): void
    {
        if (isset($data['name'])) {
            $equipmentSet->setName($data['name']);
        }
        if (isset($data['description'])) {
            $equipmentSet->setDescription($data['description']);
        }
        if (isset($data['set_type'])) {
            $equipmentSet->setSetType($data['set_type']);
        }
        if (isset($data['next_review_date'])) {
            $equipmentSet->setNextReviewDate($data['next_review_date']);
        }
        if (isset($data['review_interval_months'])) {
            $equipmentSet->setReviewIntervalMonths($data['review_interval_months']);
        }
        if (isset($data['location'])) {
            $equipmentSet->setLocation($data['location']);
        }
        if (isset($data['notes'])) {
            $equipmentSet->setNotes($data['notes']);
        }
    }

    private function getEquipmentDataArray(AsekuracyjnyEquipment $equipment): array
    {
        return [
            'name' => $equipment->getName(),
            'inventory_number' => $equipment->getInventoryNumber(),
            'description' => $equipment->getDescription(),
            'equipment_type' => $equipment->getEquipmentType(),
            'manufacturer' => $equipment->getManufacturer(),
            'model' => $equipment->getModel(),
            'serial_number' => $equipment->getSerialNumber(),
            'status' => $equipment->getStatus()
        ];
    }

    private function getEquipmentSetDataArray(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return [
            'name' => $equipmentSet->getName(),
            'description' => $equipmentSet->getDescription(),
            'set_type' => $equipmentSet->getSetType(),
            'status' => $equipmentSet->getStatus()
        ];
    }

    // === REVIEW MANAGEMENT ===

    public function getReviewsWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        return $this->reviewRepository->findWithPagination($page, $limit, $filters);
    }

    public function getReview(int $id): ?AsekuracyjnyReview
    {
        return $this->reviewRepository->find($id);
    }

    public function getReviewStatistics(): array
    {
        return $this->reviewRepository->getStatistics();
    }

    public function createReview(array $data, User $user): AsekuracyjnyReview
    {
        $review = new AsekuracyjnyReview();
        
        $this->updateReviewFromData($review, $data);
        $review->setCreatedBy($user);
        
        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            throw new ValidationException('Validation failed', $errors);
        }
        
        $this->entityManager->persist($review);
        $this->entityManager->flush();
        
        $this->auditService->logUserAction($user, 'create_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber()
        ]);
        
        return $review;
    }

    public function updateReview(AsekuracyjnyReview $review, array $data, User $user): AsekuracyjnyReview
    {
        $oldData = $this->getReviewDataArray($review);
        
        $this->updateReviewFromData($review, $data);
        $review->setUpdatedBy($user);
        
        $errors = $this->validator->validate($review);
        if (count($errors) > 0) {
            throw new ValidationException('Validation failed', $errors);
        }
        
        $this->entityManager->flush();
        
        $this->auditService->logUserAction($user, 'update_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'changes' => array_diff_assoc($this->getReviewDataArray($review), $oldData)
        ]);
        
        return $review;
    }

    public function deleteReview(AsekuracyjnyReview $review, User $user): void
    {
        if ($review->getStatus() === AsekuracyjnyReview::STATUS_COMPLETED) {
            throw new BusinessLogicException('Nie można usunąć zakończonego przeglądu');
        }
        
        $this->auditService->logUserAction($user, 'delete_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber()
        ]);
        
        $this->entityManager->remove($review);
        $this->entityManager->flush();
    }

    public function sendReview(AsekuracyjnyReview $review, User $user): AsekuracyjnyReview
    {
        if ($review->getStatus() !== AsekuracyjnyReview::STATUS_PREPARATION) {
            throw new BusinessLogicException('Przegląd można wysłać tylko ze statusu "Przygotowanie"');
        }
        
        $review->setStatus(AsekuracyjnyReview::STATUS_SENT);
        $review->setSentDate(new \DateTime());
        $review->setSentBy($user);
        $review->setUpdatedBy($user);
        
        // Zmiana statusu sprzętu lub zestawu na "w trakcie przeglądu"
        if ($review->getEquipment()) {
            // Przegląd pojedynczego sprzętu
            $equipment = $review->getEquipment();
            $equipment->setStatus(AsekuracyjnyEquipment::STATUS_IN_REVIEW);
            $equipment->setUpdatedBy($user);
        } elseif ($review->getEquipmentSet()) {
            // Przegląd zestawu - zmiana statusu zestawu i wszystkich jego elementów
            $equipmentSet = $review->getEquipmentSet();
            $equipmentSet->setStatus(AsekuracyjnyEquipmentSet::STATUS_IN_REVIEW);
            $equipmentSet->setUpdatedBy($user);
            
            // Zmiana statusu wszystkich elementów zestawu
            foreach ($equipmentSet->getEquipmentItems() as $equipment) {
                $equipment->setStatus(AsekuracyjnyEquipment::STATUS_IN_REVIEW);
                $equipment->setUpdatedBy($user);
            }
        }
        
        $this->entityManager->flush();
        
        $this->auditService->logUserAction($user, 'send_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber()
        ]);
        
        return $review;
    }

    public function completeReview(AsekuracyjnyReview $review, array $data, User $user): AsekuracyjnyReview
    {
        if ($review->getStatus() !== AsekuracyjnyReview::STATUS_SENT) {
            throw new BusinessLogicException('Przegląd można zakończyć tylko ze statusu "Wysłane"');
        }
        
        $review->setStatus(AsekuracyjnyReview::STATUS_COMPLETED);
        $review->setCompletedDate($data['completed_date'] ?? new \DateTime());
        $review->setCompletedBy($user);
        $review->setUpdatedBy($user);
        
        // Aktualizacja wyników przeglądu
        if (isset($data['result'])) {
            $review->setResult($data['result']);
        }
        if (isset($data['certificate_number'])) {
            $review->setCertificateNumber($data['certificate_number']);
        }
        if (isset($data['findings'])) {
            $review->setFindings($data['findings']);
        }
        if (isset($data['recommendations'])) {
            $review->setRecommendations($data['recommendations']);
        }
        if (isset($data['cost'])) {
            $review->setCost($data['cost']);
        }
        if (isset($data['attachments'])) {
            // Dodanie nowych załączników do istniejących
            $existingAttachments = $review->getAttachments();
            $newAttachments = array_merge($existingAttachments, $data['attachments']);
            $review->setAttachments($newAttachments);
        }
        if (isset($data['next_review_date'])) {
            $review->setNextReviewDate($data['next_review_date']);
            
            // Aktualizacja daty następnego przeglądu w sprzęcie/zestawie
            if ($review->getEquipment()) {
                $review->getEquipment()->setNextReviewDate($data['next_review_date']);
            }
            if ($review->getEquipmentSet()) {
                $equipmentSet = $review->getEquipmentSet();
                $equipmentSet->setNextReviewDate($data['next_review_date']);
                
                // Aktualizacja dat następnego przeglądu dla wszystkich elementów zestawu
                $completedDate = $review->getCompletedDate() ?? new \DateTime();
                foreach ($equipmentSet->getEquipmentItems() as $equipment) {
                    $nextReviewDate = $this->calculateNextReviewDateForEquipment($equipment, $completedDate, $data['next_review_date']);
                    $equipment->setNextReviewDate($nextReviewDate);
                    $equipment->setUpdatedBy($user);
                }
            }
        }
        
        // Przywrócenie statusu sprzętu lub zestawu po zakończeniu przeglądu
        if ($review->getEquipment()) {
            // Przegląd pojedynczego sprzętu - przywrócenie statusu na "available"
            $equipment = $review->getEquipment();
            $equipment->setStatus(AsekuracyjnyEquipment::STATUS_AVAILABLE);
            $equipment->setUpdatedBy($user);
        } elseif ($review->getEquipmentSet()) {
            // Przegląd zestawu - przywrócenie statusu zestawu i wszystkich jego elementów
            $equipmentSet = $review->getEquipmentSet();
            $equipmentSet->setStatus(AsekuracyjnyEquipmentSet::STATUS_AVAILABLE);
            $equipmentSet->setUpdatedBy($user);
            
            // Przywrócenie statusu wszystkich elementów zestawu
            foreach ($equipmentSet->getEquipmentItems() as $equipment) {
                $equipment->setStatus(AsekuracyjnyEquipment::STATUS_AVAILABLE);
                $equipment->setUpdatedBy($user);
            }
        }
        
        $this->entityManager->flush();
        
        $this->auditService->logUserAction($user, 'complete_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'result' => $review->getResult()
        ]);
        
        return $review;
    }

    private function updateReviewFromData(AsekuracyjnyReview $review, array $data): void
    {
        if (isset($data['planned_date'])) {
            $review->setPlannedDate($data['planned_date']);
        }
        if (isset($data['review_type'])) {
            $review->setReviewType($data['review_type']);
        }
        if (isset($data['review_company'])) {
            $review->setReviewCompany($data['review_company']);
        }
        if (isset($data['notes'])) {
            $review->setNotes($data['notes']);
        }
        if (isset($data['equipment'])) {
            $review->setEquipment($data['equipment']);
        }
        if (isset($data['equipment_set'])) {
            $review->setEquipmentSet($data['equipment_set']);
        }
    }

    private function getReviewDataArray(AsekuracyjnyReview $review): array
    {
        return [
            'planned_date' => $review->getPlannedDate()?->format('Y-m-d'),
            'review_type' => $review->getReviewType(),
            'review_company' => $review->getReviewCompany(),
            'notes' => $review->getNotes(),
            'status' => $review->getStatus()
        ];
    }

    private function calculateNextReviewDateForEquipment(AsekuracyjnyEquipment $equipment, \DateTimeInterface $completedDate, \DateTimeInterface $fallbackDate): \DateTimeInterface
    {
        $reviewIntervalMonths = $equipment->getReviewIntervalMonths();
        
        // Jeśli element ma ustawiony okres przeglądu, oblicz datę na podstawie daty zakończenia + okres
        if ($reviewIntervalMonths && $reviewIntervalMonths > 0) {
            $nextReviewDate = clone $completedDate;
            $nextReviewDate->modify("+{$reviewIntervalMonths} months");
            return $nextReviewDate;
        }
        
        // Jeśli element nie ma ustawionego okresu przeglądu, użyj daty z zestawu
        return $fallbackDate;
    }
}