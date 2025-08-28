<?php

namespace App\AparaturaPomiarowa\Repository;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AparaturaPomiarowaReview>
 */
class AparaturaPomiarowaReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AparaturaPomiarowaReview::class);
    }

    public function save(AparaturaPomiarowaReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AparaturaPomiarowaReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.equipment', 'e')
            ->leftJoin('r.equipmentSet', 's')
            ->leftJoin('r.preparedBy', 'pb')
            ->leftJoin('r.sentBy', 'sb')
            ->leftJoin('r.completedBy', 'cb')
            ->addSelect('e')
            ->addSelect('s')
            ->addSelect('pb')
            ->addSelect('sb')
            ->addSelect('cb');

        // Sortowanie
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        
        $validSortFields = [
            'review_number' => 'r.reviewNumber',
            'status' => 'r.status',
            'review_type' => 'r.reviewType',
            'planned_date' => 'r.plannedDate',
            'sent_date' => 'r.sentDate',
            'completed_date' => 'r.completedDate',
            'created_at' => 'r.createdAt',
            'prepared_by' => 'pb.username'
        ];
        
        if (isset($validSortFields[$sortBy])) {
            $qb->orderBy($validSortFields[$sortBy], strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('r.createdAt', 'DESC');
        }

        $this->applyFilters($qb, $filters);

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $query = $qb->getQuery();
        $items = $query->getResult();
        
        $countQb = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)');
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

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findInPreparation(): array
    {
        return $this->findByStatus(AparaturaPomiarowaReview::STATUS_PREPARATION);
    }

    public function findSent(): array
    {
        return $this->findByStatus(AparaturaPomiarowaReview::STATUS_SENT);
    }

    public function findCompleted(): array
    {
        return $this->findByStatus(AparaturaPomiarowaReview::STATUS_COMPLETED);
    }

    public function findByReviewType(string $reviewType): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reviewType = :reviewType')
            ->setParameter('reviewType', $reviewType)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.preparedBy = :user')
            ->orWhere('r.sentBy = :user')
            ->orWhere('r.completedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipment(int $equipmentId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipment = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentSet(int $equipmentSetId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipmentSet = :equipmentSetId')
            ->setParameter('equipmentSetId', $equipmentSetId)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReviews(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->where('r.status = :sentStatus')
            ->andWhere('r.plannedDate < :now')
            ->setParameter('sentStatus', AparaturaPomiarowaReview::STATUS_SENT)
            ->setParameter('now', $now)
            ->orderBy('r.plannedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingReviews(int $days = 30): array
    {
        $now = new \DateTime();
        $futureDate = clone $now;
        $futureDate->add(new \DateInterval('P' . $days . 'D'));
        
        return $this->createQueryBuilder('r')
            ->where('r.status = :sentStatus')
            ->andWhere('r.plannedDate BETWEEN :now AND :futureDate')
            ->setParameter('sentStatus', AparaturaPomiarowaReview::STATUS_SENT)
            ->setParameter('now', $now)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('r.plannedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('r');

        $total = $qb->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $inPreparation = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AparaturaPomiarowaReview::STATUS_PREPARATION)
            ->getQuery()
            ->getSingleScalarResult();

        $sent = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AparaturaPomiarowaReview::STATUS_SENT)
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AparaturaPomiarowaReview::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        $cancelled = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AparaturaPomiarowaReview::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        $overdue = count($this->findOverdueReviews());
        $upcoming = count($this->findUpcomingReviews());

        return [
            'total' => $total,
            'in_preparation' => $inPreparation,
            'sent' => $sent,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'upcoming' => $upcoming
        ];
    }

    public function findRecentlyCompleted(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.completedDate IS NOT NULL')
            ->setParameter('status', AparaturaPomiarowaReview::STATUS_COMPLETED)
            ->orderBy('r.completedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReviewNumber(string $reviewNumber): ?AparaturaPomiarowaReview
    {
        return $this->createQueryBuilder('r')
            ->where('r.reviewNumber = :reviewNumber')
            ->setParameter('reviewNumber', $reviewNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('r.reviewNumber LIKE :search OR r.reviewCompany LIKE :search OR r.certificateNumber LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['review_type'])) {
            $qb->andWhere('r.reviewType = :reviewType')
                ->setParameter('reviewType', $filters['review_type']);
        }

        if (!empty($filters['prepared_by'])) {
            $qb->andWhere('r.preparedBy = :preparedBy')
                ->setParameter('preparedBy', $filters['prepared_by']);
        }

        if (!empty($filters['result'])) {
            $qb->andWhere('r.result = :result')
                ->setParameter('result', $filters['result']);
        }

        if (!empty($filters['review_company'])) {
            $qb->andWhere('r.reviewCompany LIKE :reviewCompany')
                ->setParameter('reviewCompany', '%' . $filters['review_company'] . '%');
        }

        if (!empty($filters['planned_date_from'])) {
            $qb->andWhere('r.plannedDate >= :plannedDateFrom')
                ->setParameter('plannedDateFrom', new \DateTime($filters['planned_date_from']));
        }

        if (!empty($filters['planned_date_to'])) {
            $qb->andWhere('r.plannedDate <= :plannedDateTo')
                ->setParameter('plannedDateTo', new \DateTime($filters['planned_date_to']));
        }

        if (!empty($filters['completed_date_from'])) {
            $qb->andWhere('r.completedDate >= :completedDateFrom')
                ->setParameter('completedDateFrom', new \DateTime($filters['completed_date_from']));
        }

        if (!empty($filters['completed_date_to'])) {
            $qb->andWhere('r.completedDate <= :completedDateTo')
                ->setParameter('completedDateTo', new \DateTime($filters['completed_date_to']));
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $now = new \DateTime();
            $qb->andWhere('r.status = :sentStatus')
                ->andWhere('r.plannedDate < :now')
                ->setParameter('sentStatus', AparaturaPomiarowaReview::STATUS_SENT)
                ->setParameter('now', $now);
        }

        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $now = new \DateTime();
            $futureDate = clone $now;
            $futureDate->add(new \DateInterval('P30D'));
            
            $qb->andWhere('r.status = :sentStatus')
                ->andWhere('r.plannedDate BETWEEN :now AND :futureDate')
                ->setParameter('sentStatus', AparaturaPomiarowaReview::STATUS_SENT)
                ->setParameter('now', $now)
                ->setParameter('futureDate', $futureDate);
        }

        if (!empty($filters['equipment_id'])) {
            $qb->andWhere('r.equipment = :equipmentId')
                ->setParameter('equipmentId', $filters['equipment_id']);
        }

        if (!empty($filters['equipment_set_id'])) {
            $qb->andWhere('r.equipmentSet = :equipmentSetId')
                ->setParameter('equipmentSetId', $filters['equipment_set_id']);
        }
    }

    public function findByEquipment($equipment): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentSet($equipmentSet): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipmentSet = :equipmentSet')
            ->setParameter('equipmentSet', $equipmentSet)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}