<?php

namespace App\AsekuracyjnySPM\Repository;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AsekuracyjnyEquipment>
 */
class AsekuracyjnyEquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsekuracyjnyEquipment::class);
    }

    public function save(AsekuracyjnyEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AsekuracyjnyEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.assignedTo', 'u')
            ->leftJoin('e.equipmentSets', 'es')
            ->addSelect('u')
            ->addSelect('es');

        // Sortowanie
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'ASC';
        
        $validSortFields = [
            'name' => 'e.name',
            'inventory_number' => 'e.inventoryNumber', 
            'equipment_type' => 'e.equipmentType',
            'status' => 'e.status',
            'assigned_to' => 'u.username',
            'next_review_date' => 'e.nextReviewDate',
            'created_at' => 'e.createdAt'
        ];
        
        if (isset($validSortFields[$sortBy])) {
            $qb->orderBy($validSortFields[$sortBy], strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('e.name', 'ASC');
        }

        $this->applyFilters($qb, $filters);

        // Obsługa paginacji - jeśli limit to 0, nie stosuj ograniczeń
        if ($limit > 0) {
            $offset = ($page - 1) * $limit;
            $qb->setFirstResult($offset)->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $items = $query->getResult();
        
        $countQb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');
        $this->applyFilters($countQb, $filters);
        $total = $countQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $limit > 0 ? ceil($total / $limit) : 1
        ];
    }

    public function search(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.name LIKE :query')
            ->orWhere('e.inventoryNumber LIKE :query')
            ->orWhere('e.model LIKE :query')
            ->orWhere('e.serialNumber LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('e.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', $status)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAssignedToUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->andWhere('e.assignedTo IS NULL')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_AVAILABLE)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNeedingReview(): array
    {
        $now = new \DateTime();
        $warningDate = clone $now;
        $warningDate->add(new \DateInterval('P30D'));

        return $this->createQueryBuilder('e')
            ->where('e.nextReviewDate IS NOT NULL')
            ->andWhere('e.nextReviewDate <= :warningDate')
            ->andWhere('e.status != :inReviewStatus')
            ->setParameter('warningDate', $warningDate)
            ->setParameter('inReviewStatus', AsekuracyjnyEquipment::STATUS_IN_REVIEW)
            ->orderBy('e.nextReviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReviews(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('e')
            ->where('e.nextReviewDate IS NOT NULL')
            ->andWhere('e.nextReviewDate < :now')
            ->andWhere('e.status != :inReviewStatus')
            ->setParameter('now', $now)
            ->setParameter('inReviewStatus', AsekuracyjnyEquipment::STATUS_IN_REVIEW)
            ->orderBy('e.nextReviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentType(string $equipmentType): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.equipmentType = :equipmentType')
            ->setParameter('equipmentType', $equipmentType)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('e');

        $total = $qb->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $available = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_AVAILABLE)
            ->getQuery()
            ->getSingleScalarResult();

        $assigned = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_ASSIGNED)
            ->getQuery()
            ->getSingleScalarResult();

        $inReview = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_IN_REVIEW)
            ->getQuery()
            ->getSingleScalarResult();

        $damaged = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_DAMAGED)
            ->getQuery()
            ->getSingleScalarResult();

        $needingReview = count($this->findNeedingReview());
        $overdueReviews = count($this->findOverdueReviews());

        return [
            'total' => $total,
            'available' => $available,
            'assigned' => $assigned,
            'in_review' => $inReview,
            'damaged' => $damaged,
            'needing_review' => $needingReview,
            'overdue_reviews' => $overdueReviews
        ];
    }

    public function findByInventoryNumber(string $inventoryNumber): ?AsekuracyjnyEquipment
    {
        return $this->createQueryBuilder('e')
            ->where('e.inventoryNumber = :inventoryNumber')
            ->setParameter('inventoryNumber', $inventoryNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNotInEquipmentSet(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.equipmentSets', 'es')
            ->where('es.id IS NULL')
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailableForEquipmentSet(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.equipmentSets', 'es')
            ->where('e.status = :status')
            ->andWhere('e.assignedTo IS NULL')
            ->andWhere('es.id IS NULL')
            ->setParameter('status', AsekuracyjnyEquipment::STATUS_AVAILABLE)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('e.name LIKE :search OR e.inventoryNumber LIKE :search OR e.model LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['equipment_type'])) {
            $qb->andWhere('e.equipmentType = :equipmentType')
                ->setParameter('equipmentType', $filters['equipment_type']);
        }

        if (!empty($filters['assigned_to'])) {
            $qb->andWhere('e.assignedTo = :assignedTo')
                ->setParameter('assignedTo', $filters['assigned_to']);
        }

        if (!empty($filters['manufacturer'])) {
            $qb->andWhere('e.manufacturer LIKE :manufacturer')
                ->setParameter('manufacturer', '%' . $filters['manufacturer'] . '%');
        }

        if (isset($filters['needs_review']) && $filters['needs_review']) {
            $now = new \DateTime();
            $warningDate = clone $now;
            $warningDate->add(new \DateInterval('P30D'));
            
            $qb->andWhere('e.nextReviewDate IS NOT NULL')
                ->andWhere('e.nextReviewDate <= :warningDate')
                ->setParameter('warningDate', $warningDate);
        }

        if (isset($filters['overdue_review']) && $filters['overdue_review']) {
            $now = new \DateTime();
            
            $qb->andWhere('e.nextReviewDate IS NOT NULL')
                ->andWhere('e.nextReviewDate < :now')
                ->setParameter('now', $now);
        }

        if (!empty($filters['equipment_set_id'])) {
            if ($filters['equipment_set_id'] === 'no_set') {
                // Sprzęt nie w żadnym zestawie
                $qb->leftJoin('e.equipmentSets', 'esFilter')
                   ->andWhere('esFilter.id IS NULL');
            } else {
                // Sprzęt w konkretnym zestawie
                $qb->join('e.equipmentSets', 'esFilter')
                   ->andWhere('esFilter.id = :equipmentSetId')
                   ->setParameter('equipmentSetId', $filters['equipment_set_id']);
            }
        }
    }

    /**
     * Get all equipment sets for filtering dropdown
     */
    public function getAllEquipmentSets(): array
    {
        return $this->getEntityManager()
            ->getRepository('App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet')
            ->createQueryBuilder('es')
            ->orderBy('es.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}