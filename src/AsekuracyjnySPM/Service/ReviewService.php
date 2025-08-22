<?php

namespace App\AsekuracyjnySPM\Service;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyReviewEquipment;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentSetRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewEquipmentRepository;
use App\Entity\User;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use App\Exception\BusinessLogicException;

class ReviewService
{
    public function __construct(
        private AsekuracyjnyReviewRepository $reviewRepository,
        private AsekuracyjnyEquipmentRepository $equipmentRepository,
        private AsekuracyjnyEquipmentSetRepository $equipmentSetRepository,
        private AsekuracyjnyReviewEquipmentRepository $reviewEquipmentRepository,
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ValidatorInterface $validator
    ) {}

    // === REVIEW CREATION ===

    public function createEquipmentReview(AsekuracyjnyEquipment $equipment, array $data, User $user): AsekuracyjnyReview
    {
        $this->validateReviewData($data);
        $this->checkActiveReviewsForEquipment($equipment);

        $review = new AsekuracyjnyReview();
        $review->setEquipment($equipment);
        $review->setReviewNumber($this->generateReviewNumber());
        $this->populateReviewFromArray($review, $data);
        $review->setPreparedBy($user);
        $review->setCreatedBy($user);

        $violations = $this->validator->validate($review);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        // Create review equipment entry
        $reviewEquipment = new AsekuracyjnyReviewEquipment();
        $reviewEquipment->setReview($review);
        $reviewEquipment->setEquipment($equipment);
        $reviewEquipment->setWasInSetAtReview(false);
        $reviewEquipment->captureEquipmentSnapshot($equipment);

        $this->entityManager->persist($reviewEquipment);

        // Update equipment status
        $equipment->setStatus(AsekuracyjnyEquipment::STATUS_IN_REVIEW);
        $equipment->setUpdatedBy($user);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyReview', $review->getId(), 'CREATE', array_merge($data, [
            'type' => 'equipment_review',
            'equipment_id' => $equipment->getId()
        ]));

        $this->logger->info('Utworzono przegląd sprzętu', [
            'review_id' => $review->getId(),
            'equipment_id' => $equipment->getId(),
            'user' => $user->getUsername()
        ]);

        return $review;
    }

    public function createEquipmentSetReview(AsekuracyjnyEquipmentSet $equipmentSet, array $data, User $user, array $selectedEquipmentIds = []): AsekuracyjnyReview
    {
        $this->validateReviewData($data);
        $this->checkActiveReviewsForEquipmentSet($equipmentSet);

        if (!empty($selectedEquipmentIds)) {
            $this->validateSelectedEquipment($equipmentSet, $selectedEquipmentIds);
        }

        $review = new AsekuracyjnyReview();
        $review->setEquipmentSet($equipmentSet);
        $review->setSelectedEquipmentIds($selectedEquipmentIds);
        $review->setReviewNumber($this->generateReviewNumber());
        $this->populateReviewFromArray($review, $data);
        $review->setPreparedBy($user);
        $review->setCreatedBy($user);

        $violations = $this->validator->validate($review);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        // Create review equipment entries for all equipments in the review
        $equipmentsToReview = empty($selectedEquipmentIds) 
            ? $equipmentSet->getEquipment()->toArray()
            : $equipmentSet->getEquipment()->filter(function($equipment) use ($selectedEquipmentIds) {
                return in_array($equipment->getId(), $selectedEquipmentIds);
            })->toArray();

        foreach ($equipmentsToReview as $equipment) {
            $reviewEquipment = new AsekuracyjnyReviewEquipment();
            $reviewEquipment->setReview($review);
            $reviewEquipment->setEquipment($equipment);
            $reviewEquipment->setWasInSetAtReview(true);
            $reviewEquipment->captureEquipmentSnapshot($equipment);
            $reviewEquipment->captureSetContext($equipmentSet);

            $this->entityManager->persist($reviewEquipment);

            // Update individual equipment status
            $equipment->setStatus(AsekuracyjnyEquipment::STATUS_IN_REVIEW);
            $equipment->setUpdatedBy($user);
        }

        // Update equipment set status
        $equipmentSet->setStatus(AsekuracyjnyEquipmentSet::STATUS_IN_REVIEW);
        $equipmentSet->setUpdatedBy($user);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AsekuracyjnyReview', $review->getId(), 'CREATE', array_merge($data, [
            'type' => 'equipment_set_review',
            'equipment_set_id' => $equipmentSet->getId(),
            'selected_equipment_ids' => $selectedEquipmentIds
        ]));

        $this->logger->info('Utworzono przegląd zestawu', [
            'review_id' => $review->getId(),
            'equipment_set_id' => $equipmentSet->getId(),
            'selected_count' => count($selectedEquipmentIds),
            'user' => $user->getUsername()
        ]);

        return $review;
    }

    // === REVIEW WORKFLOW ===

    public function sendToReview(AsekuracyjnyReview $review, User $user): AsekuracyjnyReview
    {
        if (!$review->canBeSent()) {
            throw new BusinessLogicException('Przegląd nie może być wysłany w aktualnym stanie: ' . $review->getStatusDisplayName());
        }

        $review->sendToReview($user);
        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'review_sent', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'subject' => $review->getReviewSubject(),
            'review_company' => $review->getReviewCompany()
        ]);

        $this->logger->info('Wysłano przegląd', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'sent_by' => $user->getUsername()
        ]);

        return $review;
    }

    public function completeReview(AsekuracyjnyReview $review, array $data, User $user): AsekuracyjnyReview
    {
        if (!$review->canBeCompleted()) {
            throw new BusinessLogicException('Przegląd nie może być zakończony w aktualnym stanie: ' . $review->getStatusDisplayName());
        }

        $this->validateCompletionData($data);

        // Update review data
        if (isset($data['result'])) {
            $review->setResult($data['result']);
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
        if (isset($data['certificate_number'])) {
            $review->setCertificateNumber($data['certificate_number']);
        }
        if (isset($data['next_review_date'])) {
            $review->setNextReviewDate($data['next_review_date']);
        }

        $review->completeReview($user, $data['result'] ?? null);
        $this->entityManager->flush();

        // Update individual equipment review results
        $this->updateEquipmentReviewResults($review, $data);

        // Update equipment/set status and next review date
        $this->updateSubjectAfterReview($review, $data, $user);

        $this->auditService->logUserAction($user, 'review_completed', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'result' => $review->getResult(),
            'cost' => $review->getCost(),
            'next_review_date' => $review->getNextReviewDate()?->format('Y-m-d')
        ]);

        $this->logger->info('Zakończono przegląd', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'result' => $review->getResult(),
            'completed_by' => $user->getUsername()
        ]);

        return $review;
    }

    public function cancelReview(AsekuracyjnyReview $review, User $user, ?string $reason = null): AsekuracyjnyReview
    {
        if (!$review->canBeCancelled()) {
            throw new BusinessLogicException('Przegląd nie może być anulowany w aktualnym stanie: ' . $review->getStatusDisplayName());
        }

        $review->cancel();
        if ($reason) {
            $review->setNotes($review->getNotes() . "\n\nAnulowany: " . $reason);
        }
        $review->setUpdatedBy($user);
        $this->entityManager->flush();

        // Restore equipment/set status
        $this->restoreSubjectStatusAfterCancel($review, $user);

        $this->auditService->logUserAction($user, 'review_cancelled', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'reason' => $reason
        ]);

        $this->logger->info('Anulowano przegląd', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'reason' => $reason,
            'cancelled_by' => $user->getUsername()
        ]);

        return $review;
    }

    // === QUERY METHODS ===

    public function getReviewsWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        return $this->reviewRepository->findWithPagination($page, $limit, $filters);
    }

    public function searchReviews(string $query, int $limit = 10): array
    {
        return $this->reviewRepository->search($query, $limit);
    }

    public function getReviewStatistics(): array
    {
        return $this->reviewRepository->getStatistics();
    }

    public function getUpcomingReviews(int $days = 30): array
    {
        return $this->reviewRepository->findUpcomingReviews($days);
    }

    public function getOverdueReviews(): array
    {
        return $this->reviewRepository->findOverdueReviews();
    }

    public function getReviewsForEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->reviewRepository->findByEquipment($equipment);
    }

    public function getReviewsForEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return $this->reviewRepository->findByEquipmentSet($equipmentSet);
    }

    public function getReviewsByUser(User $user, string $role = 'prepared'): array
    {
        return match ($role) {
            'prepared' => $this->reviewRepository->findByPreparedBy($user),
            'sent' => $this->reviewRepository->findBySentBy($user),
            'completed' => $this->reviewRepository->findByCompletedBy($user),
            default => []
        };
    }

    // === NOTIFICATION METHODS ===

    public function getEquipmentNeedingReview(int $warningDays = 30): array
    {
        $equipment = $this->equipmentRepository->findNeedingReview();
        $equipmentSets = $this->equipmentSetRepository->findNeedingReview();

        return [
            'equipment' => $equipment,
            'equipment_sets' => $equipmentSets,
            'total_count' => count($equipment) + count($equipmentSets)
        ];
    }

    public function generateReviewReport(): array
    {
        $statistics = $this->getReviewStatistics();
        $needingReview = $this->getEquipmentNeedingReview();
        $overdue = $this->getOverdueReviews();
        $upcoming = $this->getUpcomingReviews();

        return [
            'statistics' => $statistics,
            'needing_review' => $needingReview,
            'overdue' => $overdue,
            'upcoming' => $upcoming,
            'generated_at' => new \DateTime()
        ];
    }

    // === PRIVATE HELPER METHODS ===

    private function validateReviewData(array $data): void
    {
        if (empty($data['planned_date'])) {
            throw new ValidationException('Data planowanego przeglądu jest wymagana');
        }

        if (empty($data['review_type'])) {
            throw new ValidationException('Typ przeglądu jest wymagany');
        }
    }

    private function validateCompletionData(array $data): void
    {
        if (empty($data['result'])) {
            throw new ValidationException('Wynik przeglądu jest wymagany');
        }

        if (!in_array($data['result'], array_keys(AsekuracyjnyReview::RESULTS))) {
            throw new ValidationException('Nieprawidłowy wynik przeglądu');
        }
    }

    private function checkActiveReviewsForEquipment(AsekuracyjnyEquipment $equipment): void
    {
        $activeReviews = $this->reviewRepository->getActiveReviewsForEquipment($equipment);
        if (!empty($activeReviews)) {
            throw new BusinessLogicException('Sprzęt ma już aktywny przegląd');
        }
    }

    private function checkActiveReviewsForEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): void
    {
        $activeReviews = $this->reviewRepository->getActiveReviewsForEquipmentSet($equipmentSet);
        if (!empty($activeReviews)) {
            throw new BusinessLogicException('Zestaw ma już aktywny przegląd');
        }
    }

    private function validateSelectedEquipment(AsekuracyjnyEquipmentSet $equipmentSet, array $selectedEquipmentIds): void
    {
        $setEquipmentIds = $equipmentSet->getEquipment()->map(fn($e) => $e->getId())->toArray();
        
        foreach ($selectedEquipmentIds as $equipmentId) {
            if (!in_array($equipmentId, $setEquipmentIds)) {
                throw new BusinessLogicException('Wybrany sprzęt nie należy do zestawu');
            }
        }
    }

    private function populateReviewFromArray(AsekuracyjnyReview $review, array $data): void
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
    }

    private function updateSubjectAfterReview(AsekuracyjnyReview $review, array $data, User $user): void
    {
        $nextReviewDate = $data['next_review_date'] ?? null;

        if ($review->isForSingleEquipment()) {
            $equipment = $review->getEquipment();
            
            // Update status based on result
            $newStatus = match ($review->getResult()) {
                AsekuracyjnyReview::RESULT_PASSED, 
                AsekuracyjnyReview::RESULT_CONDITIONALLY_PASSED => $equipment->isAssigned() 
                    ? AsekuracyjnyEquipment::STATUS_ASSIGNED 
                    : AsekuracyjnyEquipment::STATUS_AVAILABLE,
                AsekuracyjnyReview::RESULT_FAILED => AsekuracyjnyEquipment::STATUS_DAMAGED,
                default => AsekuracyjnyEquipment::STATUS_AVAILABLE
            };

            $equipment->setStatus($newStatus);
            if ($nextReviewDate) {
                $equipment->setNextReviewDate($nextReviewDate);
            }
            $equipment->setUpdatedBy($user);

        } elseif ($review->isForEquipmentSet()) {
            $equipmentSet = $review->getEquipmentSet();
            
            // Update status based on result
            $newStatus = match ($review->getResult()) {
                AsekuracyjnyReview::RESULT_PASSED, 
                AsekuracyjnyReview::RESULT_CONDITIONALLY_PASSED => $equipmentSet->isAssigned() 
                    ? AsekuracyjnyEquipmentSet::STATUS_ASSIGNED 
                    : AsekuracyjnyEquipmentSet::STATUS_AVAILABLE,
                AsekuracyjnyReview::RESULT_FAILED => AsekuracyjnyEquipmentSet::STATUS_INCOMPLETE,
                default => AsekuracyjnyEquipmentSet::STATUS_AVAILABLE
            };

            $equipmentSet->setStatus($newStatus);
            if ($nextReviewDate) {
                $equipmentSet->setNextReviewDate($nextReviewDate);
            }
            $equipmentSet->setUpdatedBy($user);
        }

        $this->entityManager->flush();
    }

    private function restoreSubjectStatusAfterCancel(AsekuracyjnyReview $review, User $user): void
    {
        if ($review->isForSingleEquipment()) {
            $equipment = $review->getEquipment();
            $equipment->setStatus($equipment->isAssigned() 
                ? AsekuracyjnyEquipment::STATUS_ASSIGNED 
                : AsekuracyjnyEquipment::STATUS_AVAILABLE
            );
            $equipment->setUpdatedBy($user);

        } elseif ($review->isForEquipmentSet()) {
            $equipmentSet = $review->getEquipmentSet();
            $equipmentSet->setStatus($equipmentSet->isAssigned() 
                ? AsekuracyjnyEquipmentSet::STATUS_ASSIGNED 
                : AsekuracyjnyEquipmentSet::STATUS_AVAILABLE
            );
            $equipmentSet->setUpdatedBy($user);
        }

        $this->entityManager->flush();
    }

    /**
     * Update equipment review results after review completion
     */
    private function updateEquipmentReviewResults(AsekuracyjnyReview $review, array $data): void
    {
        // Only process if review has equipment review entries
        if ($review->getReviewEquipments()->isEmpty()) {
            return;
        }

        $mainResult = $review->getResult();
        $mainFindings = $review->getFindings();
        $mainRecommendations = $review->getRecommendations();
        $nextReviewDate = $data['next_review_date'] ?? null;

        foreach ($review->getReviewEquipments() as $reviewEquipment) {
            // Set individual result to 'inherited' if not explicitly set
            if (!$reviewEquipment->getIndividualResult()) {
                $reviewEquipment->setIndividualResult(AsekuracyjnyReviewEquipment::RESULT_INHERITED);
            }

            // Set individual findings and recommendations if not explicitly set and main review has them
            if (!$reviewEquipment->getIndividualFindings() && $mainFindings) {
                $reviewEquipment->setIndividualFindings($mainFindings);
            }

            if (!$reviewEquipment->getIndividualRecommendations() && $mainRecommendations) {
                $reviewEquipment->setIndividualRecommendations($mainRecommendations);
            }

            // Set next review date for individual equipment
            if ($nextReviewDate) {
                $reviewEquipment->setIndividualNextReviewDate($nextReviewDate);
            }
        }

        $this->entityManager->flush();
    }

    // === REVIEW EQUIPMENT METHODS ===

    /**
     * Get complete review history for equipment (including when it was part of sets)
     */
    public function getEquipmentReviewHistory(AsekuracyjnyEquipment $equipment): array
    {
        return $this->reviewEquipmentRepository->findByEquipment($equipment);
    }

    /**
     * Get all equipments that were reviewed in a specific review
     */
    public function getReviewEquipments(AsekuracyjnyReview $review): array
    {
        return $this->reviewEquipmentRepository->findByReview($review);
    }

    /**
     * Get review equipment history with pagination
     */
    public function getEquipmentHistoryWithPagination(AsekuracyjnyEquipment $equipment, int $page = 1, int $limit = 25): array
    {
        return $this->reviewEquipmentRepository->findEquipmentHistoryWithPagination($equipment, $page, $limit);
    }

    // === HELPER METHODS ===

    /**
     * Generate unique review number in format PR/YYYY/MM/XXX
     */
    private function generateReviewNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        
        // Find last number in this month
        $lastReview = $this->reviewRepository->createQueryBuilder('r')
            ->where('r.reviewNumber LIKE :pattern')
            ->setParameter('pattern', "PR/{$year}/{$month}/%")
            ->orderBy('r.reviewNumber', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
            
        $nextNumber = 1;
        if ($lastReview) {
            $parts = explode('/', $lastReview->getReviewNumber());
            if (count($parts) === 4) {
                $nextNumber = intval($parts[3]) + 1;
            }
        }
        
        return sprintf('PR/%s/%s/%03d', $year, $month, $nextNumber);
    }

    /**
     * Update individual equipment result in a review
     */
    public function updateEquipmentReviewResult(
        AsekuracyjnyReview $review, 
        AsekuracyjnyEquipment $equipment, 
        string $result, 
        ?string $findings = null,
        ?string $recommendations = null,
        User $user = null
    ): bool {
        foreach ($review->getReviewEquipments() as $reviewEquipment) {
            if ($reviewEquipment->getEquipment() === $equipment) {
                $reviewEquipment->setIndividualResult($result);
                if ($findings) {
                    $reviewEquipment->setIndividualFindings($findings);
                }
                if ($recommendations) {
                    $reviewEquipment->setIndividualRecommendations($recommendations);
                }

                $this->entityManager->flush();

                if ($user) {
                    $this->auditService->logUserAction($user, 'update_equipment_review_result', [
                        'review_id' => $review->getId(),
                        'equipment_id' => $equipment->getId(),
                        'result' => $result,
                        'has_findings' => !empty($findings),
                        'has_recommendations' => !empty($recommendations)
                    ]);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Get statistics for review equipment results
     */
    public function getReviewEquipmentStatistics(): array
    {
        return $this->reviewEquipmentRepository->getResultStatistics();
    }

    /**
     * Search in review equipment history
     */
    public function searchReviewEquipmentHistory(string $query, int $limit = 10): array
    {
        return $this->reviewEquipmentRepository->search($query, $limit);
    }

    /**
     * Find orphaned review equipments (where equipment was deleted)
     */
    public function getOrphanedReviewEquipments(): array
    {
        return $this->reviewEquipmentRepository->findOrphanedReviews();
    }

    /**
     * Get equipments reviewed in a specific time period
     */
    public function getEquipmentsReviewedInPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->reviewEquipmentRepository->findByDateRange($startDate, $endDate);
    }

    /**
     * Check if equipment was ever part of a set review
     */
    public function wasEquipmentInSetReview(AsekuracyjnyEquipment $equipment): bool
    {
        $history = $this->getEquipmentReviewHistory($equipment);
        
        foreach ($history as $reviewEquipment) {
            if ($reviewEquipment->wasSetReview()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the last review equipment entry for equipment
     */
    public function getLastReviewEquipmentForEquipment(AsekuracyjnyEquipment $equipment): ?AsekuracyjnyReviewEquipment
    {
        $history = $this->getEquipmentReviewHistory($equipment);
        
        $completedReviews = array_filter($history, function(AsekuracyjnyReviewEquipment $reviewEquipment) {
            return $reviewEquipment->getReview() && $reviewEquipment->getReview()->isCompleted();
        });

        if (empty($completedReviews)) {
            return null;
        }

        usort($completedReviews, function($a, $b) {
            return $b->getReview()->getCompletedDate() <=> $a->getReview()->getCompletedDate();
        });

        return $completedReviews[0] ?? null;
    }

    // === EQUIPMENT MANAGEMENT IN REVIEWS ===

    public function addEquipmentToReview(AsekuracyjnyReview $review, AsekuracyjnyEquipment $equipment, User $user): AsekuracyjnyReviewEquipment
    {
        // Sprawdzenie czy sprzęt już nie jest w przeglądzie
        foreach ($review->getReviewEquipments() as $reviewEquipment) {
            if ($reviewEquipment->getEquipment() && $reviewEquipment->getEquipment()->getId() === $equipment->getId()) {
                throw new BusinessLogicException(sprintf('Sprzęt "%s" już znajduje się w tym przeglądzie.', $equipment->getName()));
            }
        }

        // Sprawdzenie czy przegląd nie jest zakończony
        if ($review->getStatus() === 'completed') {
            throw new BusinessLogicException('Nie można dodawać sprzętu do zakończonego przeglądu.');
        }

        // Tworzenie nowego AsekuracyjnyReviewEquipment
        $reviewEquipment = new AsekuracyjnyReviewEquipment();
        $reviewEquipment->setReview($review);
        $reviewEquipment->setEquipment($equipment);
        
        // Zapisanie aktualnych danych sprzętu na moment przeglądu
        $reviewEquipment->setEquipmentStatusAtReview($equipment->getStatus());
        $reviewEquipment->setEquipmentNameAtReview($equipment->getName());
        $reviewEquipment->setEquipmentInventoryNumberAtReview($equipment->getInventoryNumber());
        $reviewEquipment->setEquipmentTypeAtReview($equipment->getEquipmentType());
        $reviewEquipment->setEquipmentManufacturerAtReview($equipment->getManufacturer());
        $reviewEquipment->setEquipmentModelAtReview($equipment->getModel());
        $reviewEquipment->setEquipmentSerialNumberAtReview($equipment->getSerialNumber());
        $reviewEquipment->setEquipmentNextReviewDateAtReview($equipment->getNextReviewDate());
        $reviewEquipment->setWasInSetAtReview(false); // nie z zestawu

        // Zapisanie w bazie
        $this->entityManager->persist($reviewEquipment);
        $this->entityManager->flush();

        // Audit
        $this->auditService->logUserAction($user, 'add_equipment_to_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'equipment_inventory_number' => $equipment->getInventoryNumber()
        ]);

        $this->logger->info('Equipment added to review', [
            'review_id' => $review->getId(),
            'equipment_id' => $equipment->getId(),
            'user' => $user->getUsername()
        ]);

        return $reviewEquipment;
    }

    public function removeEquipmentFromReview(AsekuracyjnyReview $review, AsekuracyjnyReviewEquipment $reviewEquipment, User $user): void
    {
        // Sprawdzenie czy przegląd nie jest zakończony
        if ($review->getStatus() === 'completed') {
            throw new BusinessLogicException('Nie można usuwać sprzętu z zakończonego przeglądu.');
        }

        // Sprawdzenie czy AsekuracyjnyReviewEquipment należy do tego przeglądu
        if ($reviewEquipment->getReview()->getId() !== $review->getId()) {
            throw new BusinessLogicException('Element sprzętu nie należy do tego przeglądu.');
        }

        $equipmentName = $reviewEquipment->getEquipmentDisplayName();
        $equipmentId = $reviewEquipment->getEquipment() ? $reviewEquipment->getEquipment()->getId() : null;

        // Usunięcie z bazy
        $this->entityManager->remove($reviewEquipment);
        $this->entityManager->flush();

        // Audit
        $this->auditService->logUserAction($user, 'remove_equipment_from_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber(),
            'review_equipment_id' => $reviewEquipment->getId(),
            'equipment_id' => $equipmentId,
            'equipment_name' => $equipmentName
        ]);

        $this->logger->info('Equipment removed from review', [
            'review_id' => $review->getId(),
            'review_equipment_id' => $reviewEquipment->getId(),
            'equipment_id' => $equipmentId,
            'user' => $user->getUsername()
        ]);
    }
}