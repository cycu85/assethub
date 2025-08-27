<?php

namespace App\AparaturaPomiarowa\Repository;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<AparaturaPomiarowaTransfer>
 */
class AparaturaPomiarowaTransferRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AparaturaPomiarowaTransfer::class);
    }

    public function save(AparaturaPomiarowaTransfer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AparaturaPomiarowaTransfer $entity, bool $flush = false): void
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
            ->leftJoin('t.equipmentSet', 's')
            ->leftJoin('t.recipient', 'r')
            ->leftJoin('t.handedBy', 'hb')
            ->leftJoin('t.returnedBy', 'rb')
            ->addSelect('e')
            ->addSelect('s')
            ->addSelect('r')
            ->addSelect('hb')
            ->addSelect('rb');

        // Sortowanie
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'DESC';
        
        $validSortFields = [
            'transfer_number' => 't.transferNumber',
            'status' => 't.status',
            'transfer_date' => 't.transferDate',
            'return_date' => 't.returnDate',
            'recipient' => 'r.username',
            'handed_by' => 'hb.username',
            'created_at' => 't.createdAt'
        ];
        
        if (isset($validSortFields[$sortBy])) {
            $qb->orderBy($validSortFields[$sortBy], strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $qb->orderBy('t.createdAt', 'DESC');
        }

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

    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDrafts(): array
    {
        return $this->findByStatus(AparaturaPomiarowaTransfer::STATUS_DRAFT);
    }

    public function findInProgress(): array
    {
        return $this->findByStatus(AparaturaPomiarowaTransfer::STATUS_IN_PROGRESS);
    }

    public function findTransferred(): array
    {
        return $this->findByStatus(AparaturaPomiarowaTransfer::STATUS_TRANSFERRED);
    }

    public function findReturned(): array
    {
        return $this->findByStatus(AparaturaPomiarowaTransfer::STATUS_RETURNED);
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

    public function findByHandedBy(User $handedBy): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.handedBy = :handedBy')
            ->setParameter('handedBy', $handedBy)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipment(int $equipmentId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipment = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipmentSet(int $equipmentSetId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.equipmentSet = :equipmentSetId')
            ->setParameter('equipmentSetId', $equipmentSetId)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTransfers(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('t')
            ->where('t.status = :transferredStatus')
            ->andWhere('t.returnDate IS NOT NULL')
            ->andWhere('t.returnDate < :now')
            ->setParameter('transferredStatus', AparaturaPomiarowaTransfer::STATUS_TRANSFERRED)
            ->setParameter('now', $now)
            ->orderBy('t.returnDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveTransfers(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status IN (:activeStatuses)')
            ->setParameter('activeStatuses', [
                AparaturaPomiarowaTransfer::STATUS_IN_PROGRESS,
                AparaturaPomiarowaTransfer::STATUS_TRANSFERRED,
                AparaturaPomiarowaTransfer::STATUS_RETURN_IN_PROGRESS
            ])
            ->orderBy('t.transferDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentlyCompleted(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->andWhere('t.returnDate IS NOT NULL')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_RETURNED)
            ->orderBy('t.returnDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByTransferNumber(string $transferNumber): ?AparaturaPomiarowaTransfer
    {
        return $this->createQueryBuilder('t')
            ->where('t.transferNumber = :transferNumber')
            ->setParameter('transferNumber', $transferNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getStatistics(): array
    {
        $qb = $this->createQueryBuilder('t');

        $total = $qb->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $draft = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_DRAFT)
            ->getQuery()
            ->getSingleScalarResult();

        $inProgress = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_IN_PROGRESS)
            ->getQuery()
            ->getSingleScalarResult();

        $transferred = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_TRANSFERRED)
            ->getQuery()
            ->getSingleScalarResult();

        $returnInProgress = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_RETURN_IN_PROGRESS)
            ->getQuery()
            ->getSingleScalarResult();

        $returned = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_RETURNED)
            ->getQuery()
            ->getSingleScalarResult();

        $cancelled = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.status = :status')
            ->setParameter('status', AparaturaPomiarowaTransfer::STATUS_CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();

        $overdue = count($this->findOverdueTransfers());
        $active = count($this->findActiveTransfers());

        return [
            'total' => $total,
            'draft' => $draft,
            'in_progress' => $inProgress,
            'transferred' => $transferred,
            'return_in_progress' => $returnInProgress,
            'returned' => $returned,
            'cancelled' => $cancelled,
            'overdue' => $overdue,
            'active' => $active
        ];
    }

    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.transferDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.transferDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findCurrentlyAssignedToUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.recipient = :user')
            ->andWhere('t.status IN (:activeStatuses)')
            ->setParameter('user', $user)
            ->setParameter('activeStatuses', [
                AparaturaPomiarowaTransfer::STATUS_TRANSFERRED,
                AparaturaPomiarowaTransfer::STATUS_RETURN_IN_PROGRESS
            ])
            ->orderBy('t.transferDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findWithoutProtocol(): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :inProgressStatus')
            ->andWhere('t.protocolScanFilename IS NULL')
            ->setParameter('inProgressStatus', AparaturaPomiarowaTransfer::STATUS_IN_PROGRESS)
            ->orderBy('t.transferDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['search'])) {
            $qb->andWhere('t.transferNumber LIKE :search OR t.purpose LIKE :search OR t.notes LIKE :search')
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

        if (!empty($filters['transfer_date_from'])) {
            $qb->andWhere('t.transferDate >= :transferDateFrom')
                ->setParameter('transferDateFrom', new \DateTime($filters['transfer_date_from']));
        }

        if (!empty($filters['transfer_date_to'])) {
            $qb->andWhere('t.transferDate <= :transferDateTo')
                ->setParameter('transferDateTo', new \DateTime($filters['transfer_date_to']));
        }

        if (!empty($filters['return_date_from'])) {
            $qb->andWhere('t.returnDate >= :returnDateFrom')
                ->setParameter('returnDateFrom', new \DateTime($filters['return_date_from']));
        }

        if (!empty($filters['return_date_to'])) {
            $qb->andWhere('t.returnDate <= :returnDateTo')
                ->setParameter('returnDateTo', new \DateTime($filters['return_date_to']));
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $now = new \DateTime();
            $qb->andWhere('t.status = :transferredStatus')
                ->andWhere('t.returnDate IS NOT NULL')
                ->andWhere('t.returnDate < :now')
                ->setParameter('transferredStatus', AparaturaPomiarowaTransfer::STATUS_TRANSFERRED)
                ->setParameter('now', $now);
        }

        if (isset($filters['active']) && $filters['active']) {
            $qb->andWhere('t.status IN (:activeStatuses)')
                ->setParameter('activeStatuses', [
                    AparaturaPomiarowaTransfer::STATUS_IN_PROGRESS,
                    AparaturaPomiarowaTransfer::STATUS_TRANSFERRED,
                    AparaturaPomiarowaTransfer::STATUS_RETURN_IN_PROGRESS
                ]);
        }

        if (!empty($filters['equipment_id'])) {
            $qb->andWhere('t.equipment = :equipmentId')
                ->setParameter('equipmentId', $filters['equipment_id']);
        }

        if (!empty($filters['equipment_set_id'])) {
            $qb->andWhere('t.equipmentSet = :equipmentSetId')
                ->setParameter('equipmentSetId', $filters['equipment_set_id']);
        }

        if (isset($filters['has_protocol']) && $filters['has_protocol'] !== '') {
            if ($filters['has_protocol']) {
                $qb->andWhere('t.protocolScanFilename IS NOT NULL');
            } else {
                $qb->andWhere('t.protocolScanFilename IS NULL');
            }
        }
    }
}