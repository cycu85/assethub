<?php

namespace App\AsekuracyjnySPM\Repository;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyTransfer;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AsekuracyjnyTransfer>
 */
class AsekuracyjnyTransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsekuracyjnyTransfer::class);
    }

    public function save(AsekuracyjnyTransfer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AsekuracyjnyTransfer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithPagination(int $page = 1, int $limit = 25, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.equipment', 'e')
            ->leftJoin('t.equipmentSet', 'es')
            ->leftJoin('t.recipient', 'r')
            ->leftJoin('t.handedBy', 'hb')
            ->leftJoin('t.returnedBy', 'rb')
            ->addSelect('e')
            ->addSelect('es')
            ->addSelect('r')
            ->addSelect('hb')
            ->addSelect('rb')
            ->orderBy('t.createdAt', 'DESC');

        $this->applyFilters($qb, $filters);

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $query = $qb->getQuery();
        $items = $query->getResult();
        
        $countQb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');
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
        return $this->createQueryBuilder('t')
            ->leftJoin('t.equipment', 'e')
            ->leftJoin('t.equipmentSet', 'es')
            ->leftJoin('t.recipient', 'r')
            ->where('t.transferNumber LIKE :query')
            ->orWhere('e.name LIKE :query')
            ->orWhere('es.name LIKE :query')
            ->orWhere('r.fullName LIKE :query')
            ->orWhere('r.username LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipmentSet = :equipmentSet')
            ->setParameter('equipmentSet', $equipmentSet)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByRecipient(User $recipient): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.recipient = :user')
            ->andWhere('t.status = :activeStatus')
            ->setParameter('user', $user)
            ->setParameter('activeStatus', AsekuracyjnyTransfer::STATUS_ACTIVE)
            ->orderBy('t.transferDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDrafts(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_DRAFT)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findGenerated(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_GENERATED)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_ACTIVE)
            ->orderBy('t.transferDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCompleted(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_COMPLETED)
            ->orderBy('t.returnDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTransfers(): array
    {
        $now = new \DateTime();

        return $this->createQueryBuilder('t')
            ->where('t.status = :activeStatus')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :now')
            ->setParameter('activeStatus', AsekuracyjnyTransfer::STATUS_ACTIVE)
            ->setParameter('now', $now)
            ->orderBy('t.returnDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.transferDate >= :startDate')
            ->andWhere('t.transferDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.transferDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByHandedBy(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.handedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReturnedBy(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.returnedBy = :user')
            ->setParameter('user', $user)
            ->orderBy('t.returnDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingReturns(int $days = 7): array
    {
        $now = new \DateTime();
        $endDate = clone $now;
        $endDate->add(new \DateInterval('P' . $days . 'D'));

        return $this->createQueryBuilder('t')
            ->where('t.status = :activeStatus')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate >= :now')
            ->andWhere('t.returnDate <= :endDate')
            ->setParameter('activeStatus', AsekuracyjnyTransfer::STATUS_ACTIVE)
            ->setParameter('now', $now)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.returnDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithoutProtocolScan(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :generatedStatus')
            ->andWhere('t.protocolScanFilename IS NULL')
            ->setParameter('generatedStatus', AsekuracyjnyTransfer::STATUS_GENERATED)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getStatistics(): array
    {
        $total = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $draft = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_DRAFT)
            ->getQuery()
            ->getSingleScalarResult();

        $generated = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_GENERATED)
            ->getQuery()
            ->getSingleScalarResult();

        $active = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        $completed = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_COMPLETED)
            ->getQuery()
            ->getSingleScalarResult();

        $cancelled = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AsekuracyjnyTransfer::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        $overdue = count($this->findOverdueTransfers());
        $upcomingReturns = count($this->findUpcomingReturns());
        $withoutScan = count($this->findWithoutProtocolScan());

        return [
            'total' => $total,
            'draft' => $draft,
            'generated' => $generated,
            'active' => $active,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'upcoming_returns' => $upcomingReturns,
            'without_protocol_scan' => $withoutScan
        ];
    }

    public function findByTransferNumber(string $transferNumber): ?AsekuracyjnyTransfer
    {
        return $this->createQueryBuilder('t')
            ->where('t.transferNumber = :transferNumber')
            ->setParameter('transferNumber', $transferNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getActiveTransfersForEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipment = :equipment')
            ->andWhere('t.status IN (:activeStatuses)')
            ->setParameter('equipment', $equipment)
            ->setParameter('activeStatuses', [
                AsekuracyjnyTransfer::STATUS_GENERATED,
                AsekuracyjnyTransfer::STATUS_ACTIVE
            ])
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getActiveTransfersForEquipmentSet(AsekuracyjnyEquipmentSet $equipmentSet): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipmentSet = :equipmentSet')
            ->andWhere('t.status IN (:activeStatuses)')
            ->setParameter('equipmentSet', $equipmentSet)
            ->setParameter('activeStatuses', [
                AsekuracyjnyTransfer::STATUS_GENERATED,
                AsekuracyjnyTransfer::STATUS_ACTIVE
            ])
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->leftJoin('t.equipment', 'e')
                ->leftJoin('t.equipmentSet', 'es')
                ->leftJoin('t.recipient', 'r')
                ->andWhere('t.transferNumber LIKE :search OR e.name LIKE :search OR es.name LIKE :search OR r.fullName LIKE :search OR r.username LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['recipient'])) {
            $qb->andWhere('t.recipient = :recipient')
                ->setParameter('recipient', $filters['recipient']);
        }

        if (!empty($filters['handed_by'])) {
            $qb->andWhere('t.handedBy = :handedBy')
                ->setParameter('handedBy', $filters['handed_by']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('t.transferDate >= :dateFrom')
                ->setParameter('dateFrom', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('t.transferDate <= :dateTo')
                ->setParameter('dateTo', $filters['date_to']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $now = new \DateTime();
            
            $qb->andWhere('t.status = :activeStatus')
                ->andWhere('t.returnDate IS NOT NULL')
                ->andWhere('t.returnDate < :now')
                ->setParameter('activeStatus', AsekuracyjnyTransfer::STATUS_ACTIVE)
                ->setParameter('now', $now);
        }

        if (isset($filters['upcoming_returns']) && $filters['upcoming_returns']) {
            $now = new \DateTime();
            $endDate = clone $now;
            $endDate->add(new \DateInterval('P7D'));
            
            $qb->andWhere('t.status = :activeStatus')
                ->andWhere('t.returnDate IS NOT NULL')
                ->andWhere('t.returnDate >= :now')
                ->andWhere('t.returnDate <= :endDate')
                ->setParameter('activeStatus', AsekuracyjnyTransfer::STATUS_ACTIVE)
                ->setParameter('now', $now)
                ->setParameter('endDate', $endDate);
        }

        if (isset($filters['without_protocol_scan']) && $filters['without_protocol_scan']) {
            $qb->andWhere('t.status = :generatedStatus')
                ->andWhere('t.protocolScanFilename IS NULL')
                ->setParameter('generatedStatus', AsekuracyjnyTransfer::STATUS_GENERATED);
        }
    }
}