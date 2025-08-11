<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\EquipmentLog;
use App\Entity\EquipmentCategory;
use App\Entity\User;
use App\Exception\BusinessLogicException;
use App\Exception\ValidationException;
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentCategoryRepository;
use App\Repository\EquipmentLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EquipmentService
{
    public function __construct(
        private EquipmentRepository $equipmentRepository,
        private EquipmentCategoryRepository $categoryRepository,
        private EquipmentLogRepository $logRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private PaginatorInterface $paginator
    ) {
    }

    /**
     * Pobiera sprzęt z paginacją i filtrami
     */
    public function getEquipmentWithPagination(int $page = 1, int $limit = 25, array $filters = []): object
    {
        $queryBuilder = $this->equipmentRepository->createQueryBuilder('e')
            ->leftJoin('e.category', 'c')
            ->leftJoin('e.assignedTo', 'u');

        // Zastosuj filtry
        if (!empty($filters['status'])) {
            $queryBuilder->andWhere('e.status = :status')
                         ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['category'])) {
            $queryBuilder->andWhere('e.category = :category')
                         ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['assigned_to'])) {
            $queryBuilder->andWhere('e.assignedTo = :assignedTo')
                         ->setParameter('assignedTo', $filters['assigned_to']);
        }

        if (!empty($filters['search'])) {
            $queryBuilder->andWhere('e.name LIKE :search OR e.inventoryNumber LIKE :search OR e.serialNumber LIKE :search OR e.manufacturer LIKE :search OR e.model LIKE :search')
                         ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['warranty_expiring'])) {
            $queryBuilder->andWhere('e.warrantyExpiry IS NOT NULL')
                         ->andWhere('e.warrantyExpiry <= :warrantyCutoff')
                         ->setParameter('warrantyCutoff', new \DateTime('+30 days'));
        }

        $queryBuilder->orderBy('e.createdAt', 'DESC');

        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Tworzy nowy sprzęt
     */
    public function createEquipment(array $data, User $creator): Equipment
    {
        $equipment = new Equipment();
        $this->populateEquipmentFromArray($equipment, $data);

        // Walidacja
        $violations = $this->validator->validate($equipment);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidationException('Błędy walidacji sprzętu', $errors);
        }

        // Sprawdź unikalność numeru inwentarzowego
        if ($equipment->getInventoryNumber() && 
            $this->equipmentRepository->findOneBy(['inventoryNumber' => $equipment->getInventoryNumber()])) {
            throw new BusinessLogicException('Sprzęt o tym numerze inwentarzowym już istnieje');
        }

        // Sprawdź unikalność numeru seryjnego
        if ($equipment->getSerialNumber() && 
            $this->equipmentRepository->findOneBy(['serialNumber' => $equipment->getSerialNumber()])) {
            throw new BusinessLogicException('Sprzęt o tym numerze seryjnym już istnieje');
        }

        $equipment->setCreatedBy($creator);
        $equipment->setCreatedAt(new \DateTime());

        $this->entityManager->persist($equipment);
        $this->entityManager->flush();

        // Utwórz log
        $this->createEquipmentLog(
            $equipment,
            'created',
            'Sprzęt został utworzony',
            $creator,
            ['inventory_number' => $equipment->getInventoryNumber()]
        );

        // Audyt
        $this->auditService->logCrudOperation(
            $creator,
            'create',
            'Equipment',
            $equipment->getId(),
            [
                'name' => $equipment->getName(),
                'inventory_number' => $equipment->getInventoryNumber(),
                'category' => $equipment->getCategory()?->getName()
            ]
        );

        return $equipment;
    }

    /**
     * Aktualizuje sprzęt
     */
    public function updateEquipment(Equipment $equipment, array $data, User $updater): Equipment
    {
        $oldData = [
            'name' => $equipment->getName(),
            'status' => $equipment->getStatus(),
            'assignedTo' => $equipment->getAssignedTo()?->getId(),
            'location' => $equipment->getLocation(),
            'warrantyExpiry' => $equipment->getWarrantyExpiry()
        ];

        $this->populateEquipmentFromArray($equipment, $data);

        // Walidacja
        $violations = $this->validator->validate($equipment);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidationException('Błędy walidacji sprzętu', $errors);
        }

        // Sprawdź unikalność numerów jeśli się zmieniły
        if (isset($data['inventoryNumber']) && $data['inventoryNumber'] !== $equipment->getInventoryNumber()) {
            $existing = $this->equipmentRepository->findOneBy(['inventoryNumber' => $data['inventoryNumber']]);
            if ($existing && $existing->getId() !== $equipment->getId()) {
                throw new BusinessLogicException('Sprzęt o tym numerze inwentarzowym już istnieje');
            }
        }

        $equipment->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        // Loguj zmiany
        $changes = [];
        foreach ($oldData as $field => $oldValue) {
            $newValue = match ($field) {
                'name' => $equipment->getName(),
                'status' => $equipment->getStatus(),
                'assignedTo' => $equipment->getAssignedTo()?->getId(),
                'location' => $equipment->getLocation(),
                'warrantyExpiry' => $equipment->getWarrantyExpiry()
            };

            if ($oldValue !== $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        if (!empty($changes)) {
            $this->createEquipmentLog(
                $equipment,
                'updated',
                'Sprzęt został zaktualizowany',
                $updater,
                $changes
            );

            $this->auditService->logCrudOperation(
                $updater,
                'update',
                'Equipment',
                $equipment->getId(),
                $changes
            );
        }

        return $equipment;
    }

    /**
     * Przypisuje sprzęt do użytkownika
     */
    public function assignEquipment(Equipment $equipment, User $assignedTo, User $assigner, string $notes = ''): void
    {
        $oldAssignee = $equipment->getAssignedTo();
        
        if ($oldAssignee && $oldAssignee->getId() === $assignedTo->getId()) {
            throw new BusinessLogicException('Sprzęt jest już przypisany do tego użytkownika');
        }

        $equipment->setAssignedTo($assignedTo);
        $equipment->setStatus('assigned');
        $equipment->setAssignedAt(new \DateTime());
        $equipment->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        // Log
        $logMessage = $oldAssignee 
            ? "Sprzęt przeniesiony z {$oldAssignee->getFullName()} do {$assignedTo->getFullName()}"
            : "Sprzęt przypisany do {$assignedTo->getFullName()}";

        if ($notes) {
            $logMessage .= ". Notatki: {$notes}";
        }

        $this->createEquipmentLog(
            $equipment,
            'assigned',
            $logMessage,
            $assigner,
            [
                'old_assignee_id' => $oldAssignee?->getId(),
                'new_assignee_id' => $assignedTo->getId(),
                'notes' => $notes
            ]
        );

        $this->auditService->logUserAction($assigner, 'assign_equipment', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'old_assignee' => $oldAssignee?->getFullName(),
            'new_assignee' => $assignedTo->getFullName(),
            'notes' => $notes
        ]);
    }

    /**
     * Zwraca sprzęt (usuwa przypisanie)
     */
    public function unassignEquipment(Equipment $equipment, User $processor, string $notes = ''): void
    {
        $oldAssignee = $equipment->getAssignedTo();
        
        if (!$oldAssignee) {
            throw new BusinessLogicException('Sprzęt nie jest przypisany do żadnego użytkownika');
        }

        $equipment->setAssignedTo(null);
        $equipment->setStatus('available');
        $equipment->setAssignedAt(null);
        $equipment->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $logMessage = "Sprzęt zwrócony przez {$oldAssignee->getFullName()}";
        if ($notes) {
            $logMessage .= ". Notatki: {$notes}";
        }

        $this->createEquipmentLog(
            $equipment,
            'returned',
            $logMessage,
            $processor,
            [
                'old_assignee_id' => $oldAssignee->getId(),
                'notes' => $notes
            ]
        );

        $this->auditService->logUserAction($processor, 'unassign_equipment', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'old_assignee' => $oldAssignee->getFullName(),
            'notes' => $notes
        ]);
    }

    /**
     * Oznacza sprzęt jako uszkodzony
     */
    public function markAsDamaged(Equipment $equipment, User $reporter, string $description): void
    {
        $equipment->setStatus('damaged');
        $equipment->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->createEquipmentLog(
            $equipment,
            'damaged',
            "Sprzęt oznaczony jako uszkodzony: {$description}",
            $reporter,
            ['damage_description' => $description]
        );

        $this->auditService->logUserAction($reporter, 'mark_equipment_damaged', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'description' => $description
        ]);
    }

    /**
     * Oznacza sprzęt jako naprawiony
     */
    public function markAsRepaired(Equipment $equipment, User $repairer, string $repairNotes, float $cost = 0): void
    {
        $equipment->setStatus('available');
        $equipment->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        $logMessage = "Sprzęt naprawiony: {$repairNotes}";
        if ($cost > 0) {
            $logMessage .= " (Koszt: {$cost} PLN)";
        }

        $this->createEquipmentLog(
            $equipment,
            'repaired',
            $logMessage,
            $repairer,
            [
                'repair_notes' => $repairNotes,
                'repair_cost' => $cost
            ]
        );

        $this->auditService->logUserAction($repairer, 'mark_equipment_repaired', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'repair_notes' => $repairNotes,
            'cost' => $cost
        ]);
    }

    /**
     * Usuwa sprzęt (soft delete)
     */
    public function deleteEquipment(Equipment $equipment, User $deleter, string $reason = ''): void
    {
        if ($equipment->getAssignedTo()) {
            throw new BusinessLogicException('Nie można usunąć sprzętu przypisanego do użytkownika');
        }

        $equipment->setDeletedAt(new \DateTime());
        $equipment->setStatus('deleted');

        $this->entityManager->flush();

        $this->createEquipmentLog(
            $equipment,
            'deleted',
            "Sprzęt usunięty. Powód: {$reason}",
            $deleter,
            ['deletion_reason' => $reason]
        );

        $this->auditService->logCrudOperation(
            $deleter,
            'delete',
            'Equipment',
            $equipment->getId(),
            [
                'name' => $equipment->getName(),
                'inventory_number' => $equipment->getInventoryNumber(),
                'reason' => $reason
            ]
        );
    }

    /**
     * Tworzy log sprzętu
     */
    public function createEquipmentLog(
        Equipment $equipment,
        string $action,
        string $description,
        User $user,
        array $details = []
    ): EquipmentLog {
        $log = new EquipmentLog();
        $log->setEquipment($equipment);
        $log->setAction($action);
        $log->setDescription($description);
        $log->setUser($user);
        $log->setLogDate(new \DateTime());
        
        if (!empty($details)) {
            $log->setDetails($details);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();

        return $log;
    }

    /**
     * Pobiera sprzęt przypisany do użytkownika
     */
    public function getUserEquipment(User $user): array
    {
        return $this->equipmentRepository->findBy(['assignedTo' => $user]);
    }

    public function getUserAssignedEquipment(User $user): array
    {
        return $this->equipmentRepository->findBy(['assignedTo' => $user]);
    }

    /**
     * Pobiera sprzęt z kategorii
     */
    public function getEquipmentByCategory(int $categoryId): array
    {
        $category = $this->categoryRepository->find($categoryId);
        
        if (!$category) {
            return ['category' => null, 'equipment' => []];
        }
        
        $equipment = $this->equipmentRepository->findBy(['category' => $category]);
        
        return ['category' => $category, 'equipment' => $equipment];
    }

    /**
     * Pobiera sprzęt wymagający przeglądu
     */
    public function getEquipmentDueForInspection(): array
    {
        return $this->equipmentRepository->findDueForInspection();
    }

    /**
     * Pobiera sprzęt z wygasającą gwarancją
     */
    public function getEquipmentWithExpiringWarranty(int $daysAhead = 30): array
    {
        $cutoffDate = new \DateTime("+{$daysAhead} days");
        
        return $this->equipmentRepository->createQueryBuilder('e')
            ->where('e.warrantyExpiry IS NOT NULL')
            ->andWhere('e.warrantyExpiry <= :cutoff')
            ->andWhere('e.warrantyExpiry >= :now')
            ->setParameter('cutoff', $cutoffDate)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.warrantyExpiry', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Pobiera statystyki sprzętu
     */
    public function getEquipmentStatistics(): array
    {
        $statusStats = $this->equipmentRepository->getStatisticsByStatus();
        $totalValue = $this->equipmentRepository->getTotalValue();

        $total = array_sum(array_column($statusStats, 'count'));
        
        return [
            'total' => $total,
            'by_status' => $statusStats,
            'total_value' => $totalValue,
            'due_for_inspection' => count($this->getEquipmentDueForInspection()),
            'warranty_expiring' => count($this->getEquipmentWithExpiringWarranty())
        ];
    }

    /**
     * Wyszukuje sprzęt
     */
    public function searchEquipment(string $query, int $limit = 10): array
    {
        return $this->equipmentRepository->findBySearchTerm($query);
    }

    /**
     * Pobiera kategorie sprzętu
     */
    public function getActiveCategories(): array
    {
        return $this->categoryRepository->findActive();
    }

    /**
     * Wypełnia sprzęt danymi z tablicy
     */
    private function populateEquipmentFromArray(Equipment $equipment, array $data): void
    {
        if (isset($data['name'])) {
            $equipment->setName($data['name']);
        }
        if (isset($data['description'])) {
            $equipment->setDescription($data['description']);
        }
        if (isset($data['inventoryNumber'])) {
            $equipment->setInventoryNumber($data['inventoryNumber']);
        }
        if (isset($data['serialNumber'])) {
            $equipment->setSerialNumber($data['serialNumber']);
        }
        if (isset($data['manufacturer'])) {
            $equipment->setManufacturer($data['manufacturer']);
        }
        if (isset($data['model'])) {
            $equipment->setModel($data['model']);
        }
        if (isset($data['category']) && $data['category']) {
            $category = $this->categoryRepository->find($data['category']);
            $equipment->setCategory($category);
        }
        if (isset($data['status'])) {
            $equipment->setStatus($data['status']);
        }
        if (isset($data['purchasePrice'])) {
            $equipment->setPurchasePrice((float) $data['purchasePrice']);
        }
        if (isset($data['purchaseDate'])) {
            $equipment->setPurchaseDate($data['purchaseDate'] instanceof \DateTime ? $data['purchaseDate'] : new \DateTime($data['purchaseDate']));
        }
        if (isset($data['warrantyExpiry'])) {
            $equipment->setWarrantyExpiry($data['warrantyExpiry'] instanceof \DateTime ? $data['warrantyExpiry'] : new \DateTime($data['warrantyExpiry']));
        }
        if (isset($data['location'])) {
            $equipment->setLocation($data['location']);
        }
        if (isset($data['nextInspectionDate'])) {
            $equipment->setNextInspectionDate($data['nextInspectionDate'] instanceof \DateTime ? $data['nextInspectionDate'] : new \DateTime($data['nextInspectionDate']));
        }
    }
}