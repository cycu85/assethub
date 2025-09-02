<?php

namespace App\AparaturaPomiarowa\Service;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Repository\AparaturaPomiarowaTransferRepository;
use App\AparaturaPomiarowa\Repository\AparaturaPomiarowaEquipmentRepository;
use App\AparaturaPomiarowa\Repository\AparaturaPomiarowaEquipmentSetRepository;
use App\Entity\User;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;
use App\Exception\BusinessLogicException;

class AparaturaPomiarowaTransferService
{
    public function __construct(
        private AparaturaPomiarowaTransferRepository $transferRepository,
        private AparaturaPomiarowaEquipmentRepository $equipmentRepository,
        private AparaturaPomiarowaEquipmentSetRepository $equipmentSetRepository,
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ValidatorInterface $validator
    ) {}

    // === TRANSFER CREATION ===

    public function createEquipmentTransfer(AparaturaPomiarowaEquipment $equipment, array $data, User $user): AparaturaPomiarowaTransfer
    {
        $this->validateTransferData($data);
        $this->checkActiveTransfersForEquipment($equipment);

        if (!$equipment->isAvailable()) {
            throw new BusinessLogicException('Sprzęt nie jest dostępny do przekazania');
        }

        $transfer = new AparaturaPomiarowaTransfer();
        $transfer->setEquipment($equipment);
        $this->populateTransferFromArray($transfer, $data);
        $transfer->setHandedBy($user);
        $transfer->setCreatedBy($user);

        $violations = $this->validator->validate($transfer);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AparaturaPomiarowaTransfer', $transfer->getId(), 'CREATE', array_merge($data, [
            'type' => 'equipment_transfer',
            'equipment_id' => $equipment->getId()
        ]));

        $this->logger->info('Utworzono przekazanie sprzętu', [
            'transfer_id' => $transfer->getId(),
            'equipment_id' => $equipment->getId(),
            'recipient' => $transfer->getRecipient()->getUsername(),
            'user' => $user->getUsername()
        ]);

        return $transfer;
    }

    public function createEquipmentSetTransfer(AparaturaPomiarowaEquipmentSet $equipmentSet, array $data, User $user, array $selectedEquipmentIds = []): AparaturaPomiarowaTransfer
    {
        $this->validateTransferData($data);
        $this->checkActiveTransfersForEquipmentSet($equipmentSet);

        if (!$equipmentSet->isAvailable()) {
            throw new BusinessLogicException('Zestaw nie jest dostępny do przekazania');
        }

        if (!empty($selectedEquipmentIds)) {
            $this->validateSelectedEquipment($equipmentSet, $selectedEquipmentIds);
        }

        $transfer = new AparaturaPomiarowaTransfer();
        $transfer->setEquipmentSet($equipmentSet);
        $transfer->setSelectedEquipmentIds($selectedEquipmentIds);
        $this->populateTransferFromArray($transfer, $data);
        $transfer->setHandedBy($user);
        $transfer->setCreatedBy($user);

        $violations = $this->validator->validate($transfer);
        if (count($violations) > 0) {
            throw new ValidationException('Błędy walidacji', $violations);
        }

        $this->entityManager->persist($transfer);
        $this->entityManager->flush();

        $this->auditService->logCrudOperation($user, 'AparaturaPomiarowaTransfer', $transfer->getId(), 'CREATE', array_merge($data, [
            'type' => 'equipment_set_transfer',
            'equipment_set_id' => $equipmentSet->getId(),
            'selected_equipment_ids' => $selectedEquipmentIds
        ]));

        $this->logger->info('Utworzono przekazanie zestawu', [
            'transfer_id' => $transfer->getId(),
            'equipment_set_id' => $equipmentSet->getId(),
            'selected_count' => count($selectedEquipmentIds),
            'recipient' => $transfer->getRecipient()->getUsername(),
            'user' => $user->getUsername()
        ]);

        return $transfer;
    }

    // === TRANSFER WORKFLOW ===

    public function generateProtocol(AparaturaPomiarowaTransfer $transfer, User $user): AparaturaPomiarowaTransfer
    {
        if (!$transfer->canBeGenerated()) {
            throw new BusinessLogicException('Protokół nie może być wygenerowany w aktualnym stanie: ' . $transfer->getStatusDisplayName());
        }

        $transfer->generateProtocol();
        $transfer->setUpdatedBy($user);
        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'transfer_protocol_generated', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'subject' => $transfer->getTransferSubject(),
            'recipient' => $transfer->getRecipient()->getUsername()
        ]);

        $this->logger->info('Wygenerowano protokół przekazania', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'generated_by' => $user->getUsername()
        ]);

        return $transfer;
    }

    public function uploadProtocolScan(AparaturaPomiarowaTransfer $transfer, string $filename, User $user): AparaturaPomiarowaTransfer
    {
        if (!$transfer->isGenerated()) {
            throw new BusinessLogicException('Protokół musi być najpierw wygenerowany');
        }

        $transfer->uploadProtocolScan($filename);
        $transfer->setUpdatedBy($user);
        $this->entityManager->flush();

        // Update equipment/set assignment
        $this->assignSubjectToRecipient($transfer, $user);

        $this->auditService->logUserAction($user, 'transfer_protocol_uploaded', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'filename' => $filename
        ]);

        $this->logger->info('Przesłano skan protokołu przekazania', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'filename' => $filename,
            'uploaded_by' => $user->getUsername()
        ]);

        return $transfer;
    }

    public function completeTransfer(AparaturaPomiarowaTransfer $transfer, User $user): AparaturaPomiarowaTransfer
    {
        if (!$transfer->canBeCompleted()) {
            throw new BusinessLogicException('Przekazanie nie może być zakończone w aktualnym stanie: ' . $transfer->getStatusDisplayName());
        }

        $transfer->complete($user);
        $this->entityManager->flush();

        // Update equipment/set status
        $this->unassignSubjectFromRecipient($transfer, $user);

        $this->auditService->logUserAction($user, 'transfer_completed', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'recipient' => $transfer->getRecipient()->getUsername(),
            'duration_days' => $transfer->getDurationInDays()
        ]);

        $this->logger->info('Zakończono przekazanie', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'duration_days' => $transfer->getDurationInDays(),
            'completed_by' => $user->getUsername()
        ]);

        return $transfer;
    }

    public function cancelTransfer(AparaturaPomiarowaTransfer $transfer, User $user, ?string $reason = null): AparaturaPomiarowaTransfer
    {
        if (!$transfer->canBeCancelled()) {
            throw new BusinessLogicException('Przekazanie nie może być anulowane w aktualnym stanie: ' . $transfer->getStatusDisplayName());
        }

        // If transfer was active, unassign equipment
        if ($transfer->isActive()) {
            $this->unassignSubjectFromRecipient($transfer, $user);
        }

        $transfer->cancel();
        if ($reason) {
            $transfer->setNotes($transfer->getNotes() . "\n\nAnulowane: " . $reason);
        }
        $transfer->setUpdatedBy($user);
        $this->entityManager->flush();

        $this->auditService->logUserAction($user, 'transfer_cancelled', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'reason' => $reason
        ]);

        $this->logger->info('Anulowano przekazanie', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber(),
            'reason' => $reason,
            'cancelled_by' => $user->getUsername()
        ]);

        return $transfer;
    }

    // === QUERY METHODS ===

    public function getTransfersWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        return $this->transferRepository->findWithPagination($page, $limit, $filters);
    }

    public function searchTransfers(string $query, int $limit = 10): array
    {
        return $this->transferRepository->search($query, $limit);
    }

    public function getTransferStatistics(): array
    {
        return $this->transferRepository->getStatistics();
    }

    public function getActiveTransfersForUser(User $user): array
    {
        return $this->transferRepository->findActiveForUser($user);
    }

    public function getTransfersForEquipment(AparaturaPomiarowaEquipment $equipment): array
    {
        return $this->transferRepository->findByEquipment($equipment);
    }

    public function getTransfersForEquipmentSet(AparaturaPomiarowaEquipmentSet $equipmentSet): array
    {
        return $this->transferRepository->findByEquipmentSet($equipmentSet);
    }

    public function getOverdueTransfers(): array
    {
        return $this->transferRepository->findOverdueTransfers();
    }

    public function getUpcomingReturns(int $days = 7): array
    {
        return $this->transferRepository->findUpcomingReturns($days);
    }

    public function getTransfersWithoutProtocolScan(): array
    {
        return $this->transferRepository->findWithoutProtocolScan();
    }

    public function getTransfersByUser(User $user, string $role = 'recipient'): array
    {
        return match ($role) {
            'recipient' => $this->transferRepository->findByRecipient($user),
            'handed_by' => $this->transferRepository->findByHandedBy($user),
            'returned_by' => $this->transferRepository->findByReturnedBy($user),
            default => []
        };
    }

    // === NOTIFICATION METHODS ===

    public function generateTransferReport(): array
    {
        $statistics = $this->getTransferStatistics();
        $overdue = $this->getOverdueTransfers();
        $upcomingReturns = $this->getUpcomingReturns();
        $withoutScan = $this->getTransfersWithoutProtocolScan();

        return [
            'statistics' => $statistics,
            'overdue' => $overdue,
            'upcoming_returns' => $upcomingReturns,
            'without_protocol_scan' => $withoutScan,
            'generated_at' => new \DateTime()
        ];
    }

    // === PRIVATE HELPER METHODS ===

    private function validateTransferData(array $data): void
    {
        if (empty($data['recipient'])) {
            throw new ValidationException('Odbiorca jest wymagany');
        }

        if (empty($data['transfer_date'])) {
            throw new ValidationException('Data przekazania jest wymagana');
        }

        if (!$data['recipient'] instanceof User) {
            throw new ValidationException('Nieprawidłowy odbiorca');
        }
    }

    private function checkActiveTransfersForEquipment(AparaturaPomiarowaEquipment $equipment): void
    {
        $activeTransfers = $this->transferRepository->getActiveTransfersForEquipment($equipment);
        if (!empty($activeTransfers)) {
            throw new BusinessLogicException('Sprzęt ma już aktywne przekazanie');
        }
    }

    private function checkActiveTransfersForEquipmentSet(AparaturaPomiarowaEquipmentSet $equipmentSet): void
    {
        $activeTransfers = $this->transferRepository->getActiveTransfersForEquipmentSet($equipmentSet);
        if (!empty($activeTransfers)) {
            throw new BusinessLogicException('Zestaw ma już aktywne przekazanie');
        }
    }

    private function validateSelectedEquipment(AparaturaPomiarowaEquipmentSet $equipmentSet, array $selectedEquipmentIds): void
    {
        $setEquipmentIds = $equipmentSet->getEquipment()->map(fn($e) => $e->getId())->toArray();
        
        foreach ($selectedEquipmentIds as $equipmentId) {
            if (!in_array($equipmentId, $setEquipmentIds)) {
                throw new BusinessLogicException('Wybrany sprzęt nie należy do zestawu');
            }
        }
    }

    private function populateTransferFromArray(AparaturaPomiarowaTransfer $transfer, array $data): void
    {
        if (isset($data['recipient'])) {
            $transfer->setRecipient($data['recipient']);
        }
        if (isset($data['transfer_date'])) {
            $transfer->setTransferDate($data['transfer_date']);
        }
        if (isset($data['return_date'])) {
            $transfer->setReturnDate($data['return_date']);
        }
        if (isset($data['purpose'])) {
            $transfer->setPurpose($data['purpose']);
        }
        if (isset($data['notes'])) {
            $transfer->setNotes($data['notes']);
        }
        if (isset($data['conditions'])) {
            $transfer->setConditions($data['conditions']);
        }
        if (isset($data['location'])) {
            $transfer->setLocation($data['location']);
        }
    }

    private function assignSubjectToRecipient(AparaturaPomiarowaTransfer $transfer, User $user): void
    {
        if ($transfer->isForSingleEquipment()) {
            $equipment = $transfer->getEquipment();
            $equipment->setAssignedTo($transfer->getRecipient());
            $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_ASSIGNED);
            $equipment->setUpdatedBy($user);

        } elseif ($transfer->isForEquipmentSet()) {
            $equipmentSet = $transfer->getEquipmentSet();
            $equipmentSet->setAssignedTo($transfer->getRecipient());
            $equipmentSet->setStatus(AparaturaPomiarowaEquipmentSet::STATUS_ASSIGNED);
            $equipmentSet->setUpdatedBy($user);

            // If specific equipment selected, assign only those
            if ($transfer->hasSelectedEquipment()) {
                foreach ($transfer->getSelectedEquipmentIds() as $equipmentId) {
                    $equipment = $this->equipmentRepository->find($equipmentId);
                    if ($equipment && $equipmentSet->getEquipment()->contains($equipment)) {
                        $equipment->setAssignedTo($transfer->getRecipient());
                        $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_ASSIGNED);
                        $equipment->setUpdatedBy($user);
                    }
                }
            } else {
                // Assign all equipment in set
                foreach ($equipmentSet->getEquipment() as $equipment) {
                    $equipment->setAssignedTo($transfer->getRecipient());
                    $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_ASSIGNED);
                    $equipment->setUpdatedBy($user);
                }
            }
        }

        $this->entityManager->flush();
    }

    private function unassignSubjectFromRecipient(AparaturaPomiarowaTransfer $transfer, User $user): void
    {
        if ($transfer->isForSingleEquipment()) {
            $equipment = $transfer->getEquipment();
            $equipment->setAssignedTo(null);
            $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_AVAILABLE);
            $equipment->setUpdatedBy($user);

        } elseif ($transfer->isForEquipmentSet()) {
            $equipmentSet = $transfer->getEquipmentSet();
            $equipmentSet->setAssignedTo(null);
            $equipmentSet->setStatus(AparaturaPomiarowaEquipmentSet::STATUS_AVAILABLE);
            $equipmentSet->setUpdatedBy($user);

            // If specific equipment selected, unassign only those
            if ($transfer->hasSelectedEquipment()) {
                foreach ($transfer->getSelectedEquipmentIds() as $equipmentId) {
                    $equipment = $this->equipmentRepository->find($equipmentId);
                    if ($equipment && $equipmentSet->getEquipment()->contains($equipment)) {
                        $equipment->setAssignedTo(null);
                        $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_AVAILABLE);
                        $equipment->setUpdatedBy($user);
                    }
                }
            } else {
                // Unassign all equipment in set
                foreach ($equipmentSet->getEquipment() as $equipment) {
                    $equipment->setAssignedTo(null);
                    $equipment->setStatus(AparaturaPomiarowaEquipment::STATUS_AVAILABLE);
                    $equipment->setUpdatedBy($user);
                }
            }
        }

        $this->entityManager->flush();
    }
}