<?php

namespace App\AsekuracyjnySPM\Service;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyReviewRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentRepository;
use App\AsekuracyjnySPM\Repository\AsekuracyjnyEquipmentSetRepository;
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
        $this->populateReviewFromArray($review, $data);
        $review->setPreparedBy($user);
        $review->setCreatedBy($user);

        $violations = $this->validator->validate($review);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

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
        $this->populateReviewFromArray($review, $data);
        $review->setPreparedBy($user);
        $review->setCreatedBy($user);

        $violations = $this->validator->validate($review);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

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
}