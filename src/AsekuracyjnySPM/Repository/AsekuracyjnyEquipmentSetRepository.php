<?php

namespace App\AsekuracyjnySPM\Repository;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AsekuracyjnyEquipmentSet>
 */
class AsekuracyjnyEquipmentSetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsekuracyjnyEquipmentSet::class);
    }

    public function save(AsekuracyjnyEquipmentSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AsekuracyjnyEquipmentSet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.assignedTo', 'u')
            ->leftJoin('s.equipment', 'e')
            ->addSelect('u')
            ->addSelect('e')
            ->orderBy('s.name', 'ASC');

        $this->applyFilters($qb, $filters);

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $query = $qb->getQuery();
        $items = $query->getResult();
        
        $countQb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');
        $this->applyFilters($countQb, $filters);
        $total = $countQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    public function search(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :query')
            ->orWhere('s.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAssignedToUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.assignedTo = :user')
            ->setParameter('user', $user)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAvailable(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->andWhere('s.assignedTo IS NULL')
            ->setParameter('status', AsekuracyjnyEquipmentSet::STATUS_AVAILABLE)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findNeedingReview(): array
    {
        $now = new \DateTime();
        $warningDate = clone $now;
        $warningDate->add(new \DateInterval('P30D'));

        return $this->createQueryBuilder('s')
            ->where('s.nextReviewDate IS NOT NULL')
            ->andWhere('s.nextReviewDate <= :warningDate')
            ->andWhere('s.status != :inReviewStatus')
            ->setParameter('warningDate', $warningDate)
            ->setParameter('inReviewStatus', AsekuracyjnyEquipmentSet::STATUS_IN_REVIEW)
            ->orderBy('s.nextReviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReviews(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('s')
            ->where('s.nextReviewDate IS NOT NULL')
            ->andWhere('s.nextReviewDate < :now')
            ->andWhere('s.status != :inReviewStatus')
            ->setParameter('now', $now)
            ->setParameter('inReviewStatus', AsekuracyjnyEquipmentSet::STATUS_IN_REVIEW)
            ->orderBy('s.nextReviewDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySetType(string $setType): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.setType = :setType')
            ->setParameter('setType', $setType)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompleteAndAvailable(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.equipment', 'e')
            ->where('s.status = :availableStatus')
            ->andWhere('e.status = :equipmentAvailableStatus OR e.status IS NULL')
            ->setParameter('availableStatus', AsekuracyjnyEquipmentSet::STATUS_AVAILABLE)
            ->setParameter('equipmentAvailableStatus', 'available')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findIncomplete(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.equipment', 'e')
            ->where('s.status != :incompleteStatus')
            ->andWhere('e.status != :equipmentAvailableStatus')
            ->setParameter('incompleteStatus', AsekuracyjnyEquipmentSet::STATUS_INCOMPLETE)
            ->setParameter('equipmentAvailableStatus', 'available')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('s');

        $total = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $available = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', AsekuracyjnyEquipmentSet::STATUS_AVAILABLE)
            ->getQuery()
            ->getSingleScalarResult();

        $assigned = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', AsekuracyjnyEquipmentSet::STATUS_ASSIGNED)
            ->getQuery()
            ->getSingleScalarResult();

        $inReview = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', AsekuracyjnyEquipmentSet::STATUS_IN_REVIEW)
            ->getQuery()
            ->getSingleScalarResult();

        $incomplete = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.status = :status')
            ->setParameter('status', AsekuracyjnyEquipmentSet::STATUS_INCOMPLETE)
            ->getQuery()
            ->getSingleScalarResult();

        $needingReview = count($this->findNeedingReview());
        $overdueReviews = count($this->findOverdueReviews());

        return [
            'total' => $total,
            'available' => $available,
            'assigned' => $assigned,
            'in_review' => $inReview,
            'incomplete' => $incomplete,
            'needing_review' => $needingReview,
            'overdue_reviews' => $overdueReviews
        ];
    }

    public function findWithEquipmentCount(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.equipment', 'e')
            ->addSelect('COUNT(e.id) as equipment_count')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentId(int $equipmentId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.equipment', 'e')
            ->where('e.id = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('s.name LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['set_type'])) {
            $qb->andWhere('s.setType = :setType')
                ->setParameter('setType', $filters['set_type']);
        }

        if (!empty($filters['assigned_to'])) {
            $qb->andWhere('s.assignedTo = :assignedTo')
                ->setParameter('assignedTo', $filters['assigned_to']);
        }

        if (isset($filters['needs_review']) && $filters['needs_review']) {
            $now = new \DateTime();
            $warningDate = clone $now;
            $warningDate->add(new \DateInterval('P30D'));
            
            $qb->andWhere('s.nextReviewDate IS NOT NULL')
                ->andWhere('s.nextReviewDate <= :warningDate')
                ->setParameter('warningDate', $warningDate);
        }

        if (isset($filters['overdue_review']) && $filters['overdue_review']) {
            $now = new \DateTime();
            
            $qb->andWhere('s.nextReviewDate IS NOT NULL')
                ->andWhere('s.nextReviewDate < :now')
                ->setParameter('now', $now);
        }

        if (isset($filters['complete']) && $filters['complete']) {
            $qb->leftJoin('s.equipment', 'e')
                ->andWhere('e.status = :equipmentAvailableStatus')
                ->setParameter('equipmentAvailableStatus', 'available');
        }

        if (isset($filters['incomplete']) && $filters['incomplete']) {
            $qb->leftJoin('s.equipment', 'e')
                ->andWhere('e.status != :equipmentAvailableStatus')
                ->setParameter('equipmentAvailableStatus', 'available');
        }
    }
}