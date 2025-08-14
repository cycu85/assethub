<?php

namespace App\AsekuracyjnySPM\Repository;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AsekuracyjnyReview>
 */
class AsekuracyjnyReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsekuracyjnyReview::class);
    }

    public function save(AsekuracyjnyReview $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AsekuracyjnyReview $entity, bool $flush = false): void
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
            ->leftJoin('r.equipmentSet', 'es')
            ->leftJoin('r.preparedBy', 'pb')
            ->leftJoin('r.sentBy', 'sb')
            ->leftJoin('r.completedBy', 'cb')
            ->addSelect('e')
            ->addSelect('es')
            ->addSelect('pb')
            ->addSelect('sb')
            ->addSelect('cb')
            ->orderBy('r.createdAt', 'DESC');

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

    public function search(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.equipment', 'e')
            ->leftJoin('r.equipmentSet', 'es')
            ->where('r.reviewNumber LIKE :query')
            ->orWhere('e.name LIKE :query')
            ->orWhere('es.name LIKE :query')
            ->orWhere('r.reviewCompany LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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

    public function findByEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipmentSet = :equipmentSet')
            ->setParameter('equipmentSet', $equipmentSet)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findInPreparation(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_PREPARATION)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findSentToReview(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_SENT)
            ->orderBy('r.sentDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompleted(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_COMPLETED)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReviews(): array
    {
        $overdueDate = new \DateTime();
        $overdueDate->modify('-30 days');

        return $this->createQueryBuilder('r')
            ->where('r.status = :status')
            ->andWhere('r.sentDate < :overdueDate')
            ->setParameter('status', AsekuracyjnyReview::STATUS_SENT)
            ->setParameter('overdueDate', $overdueDate)
            ->orderBy('r.sentDate', 'ASC')
            ->getQuery()
            ->getResult();
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

    public function findByPreparedBy(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.preparedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySentBy(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.sentBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.sentDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByCompletedBy(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.completedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReviewCompany(string $reviewCompany): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.reviewCompany LIKE :reviewCompany')
            ->setParameter('reviewCompany', '%' . $reviewCompany . '%')
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.plannedDate >= :startDate')
            ->andWhere('r.plannedDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.plannedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingReviews(int $days = 30): array
    {
        $now = new \DateTime();
        $endDate = clone $now;
        $endDate->add(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('r')
            ->where('r.plannedDate >= :now')
            ->andWhere('r.plannedDate <= :endDate')
            ->andWhere('r.status = :preparationStatus')
            ->setParameter('now', $now)
            ->setParameter('endDate', $endDate)
            ->setParameter('preparationStatus', AsekuracyjnyReview::STATUS_PREPARATION)
            ->orderBy('r.plannedDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $inPreparation = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_PREPARATION)
            ->getQuery()
            ->getSingleScalarResult();

        $sent = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_SENT)
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracyjnyReview::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        $cancelled = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.status = :status')
            ->setParameter('status', AsekuracijnyReview::STATUS_CANCELLED)
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

    public function findByReviewNumber(string $reviewNumber): ?AsekuracyjnyReview
    {
        return $this->createQueryBuilder('r')
            ->where('r.reviewNumber = :reviewNumber')
            ->setParameter('reviewNumber', $reviewNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActiveReviewsForEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipment = :equipment')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('equipment', $equipment)
            ->setParameter('activeStatuses', [
                AsekuracyjnyReview::STATUS_PREPARATION,
                AsekuracijnyReview::STATUS_SENT
            ])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getActiveReviewsForEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.equipmentSet = :equipmentSet')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('equipmentSet', $equipmentSet)
            ->setParameter('activeStatuses', [
                AsekuracyjnyReview::STATUS_PREPARATION,
                AsekuracyjnyReview::STATUS_SENT
            ])
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->leftJoin('r.equipment', 'e')
                ->leftJoin('r.equipmentSet', 'es')
                ->andWhere('r.reviewNumber LIKE :search OR e.name LIKE :search OR es.name LIKE :search OR r.reviewCompany LIKE :search')
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

        if (!empty($filters['review_company'])) {
            $qb->andWhere('r.reviewCompany LIKE :reviewCompany')
                ->setParameter('reviewCompany', '%' . $filters['review_company'] . '%');
        }

        if (!empty($filters['prepared_by'])) {
            $qb->andWhere('r.preparedBy = :preparedBy')
                ->setParameter('preparedBy', $filters['prepared_by']);
        }

        if (!empty($filters['result'])) {
            $qb->andWhere('r.result = :result')
                ->setParameter('result', $filters['result']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('r.plannedDate >= :dateFrom')
                ->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('r.plannedDate <= :dateTo')
                ->setParameter('dateTo', $filters['date_to']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $overdueDate = new \DateTime();
            $overdueDate->modify('-30 days');
            
            $qb->andWhere('r.status = :sentStatus')
                ->andWhere('r.sentDate < :overdueDate')
                ->setParameter('sentStatus', AsekuracyjnyReview::STATUS_SENT)
                ->setParameter('overdueDate', $overdueDate);
        }

        if (isset($filters['upcoming']) && $filters['upcoming']) {
            $now = new \DateTime();
            $endDate = clone $now;
            $endDate->add(new \DateInterval('P30D'));
            
            $qb->andWhere('r.plannedDate >= :now')
                ->andWhere('r.plannedDate <= :endDate')
                ->setParameter('now', $now)
                ->setParameter('endDate', $endDate);
        }
    }
}