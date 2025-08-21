<?php

namespace App\AsekuracyjnySPM\Repository;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReviewEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AsekuracyjnyReviewEquipment>
 */
class AsekuracyjnyReviewEquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AsekuracyjnyReviewEquipment::class);
    }

    public function save(AsekuracyjnyReviewEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AsekuracyjnyReviewEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all review equipments for a specific equipment (complete history)
     */
    public function findByEquipment(AsekuracyjnyEquipment $equipment): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('r.completedDate', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all equipments that were reviewed in a specific review
     */
    public function findByReview(AsekuracyjnyReview $review): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->where('re.review = :review')
            ->setParameter('review', $review)
            ->orderBy('re.equipmentNameAtReview', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find equipments with specific review results
     */
    public function findByResult(string $result): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->leftJoin('re.equipment', 'e')
            ->where('re.individualResult = :result OR (re.individualResult IS NULL AND r.result = :result)')
            ->setParameter('result', $result)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find review equipment entries that were part of set reviews
     */
    public function findSetReviews(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.wasInSetAtReview = :wasInSet')
            ->setParameter('wasInSet', true)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find review equipment entries that were individual reviews
     */
    public function findIndividualReviews(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.wasInSetAtReview = :wasInSet')
            ->setParameter('wasInSet', false)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find equipments where the equipment entity no longer exists (orphaned reviews)
     */
    public function findOrphanedReviews(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.equipment IS NULL')
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get review history for equipment with pagination
     */
    public function findEquipmentHistoryWithPagination(AsekuracyjnyEquipment $equipment, int $page = 1, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('r.completedDate', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC');

        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)->setMaxResults($limit);

        $items = $qb->getQuery()->getResult();
        
        $countQb = $this->createQueryBuilder('re')
            ->select('COUNT(re.id)')
            ->where('re.equipment = :equipment')
            ->setParameter('equipment', $equipment);
        $total = $countQb->getQuery()->getSingleScalarResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get statistics for review equipment results
     */
    public function getResultStatistics(): array
    {
        $qb = $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('r.status = :completedStatus')
            ->setParameter('completedStatus', AsekuracyjnyReview::STATUS_COMPLETED);

        $total = (clone $qb)
            ->select('COUNT(re.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $passed = (clone $qb)
            ->select('COUNT(re.id)')
            ->andWhere('(re.individualResult = :passed OR (re.individualResult IS NULL AND r.result = :passed))')
            ->setParameter('passed', AsekuracyjnyReviewEquipment::RESULT_PASSED)
            ->getQuery()
            ->getSingleScalarResult();

        $failed = (clone $qb)
            ->select('COUNT(re.id)')
            ->andWhere('(re.individualResult = :failed OR (re.individualResult IS NULL AND r.result = :failed))')
            ->setParameter('failed', AsekuracyjnyReviewEquipment::RESULT_FAILED)
            ->getQuery()
            ->getSingleScalarResult();

        $conditionallyPassed = (clone $qb)
            ->select('COUNT(re.id)')
            ->andWhere('(re.individualResult = :conditionallyPassed OR (re.individualResult IS NULL AND r.result = :conditionallyPassed))')
            ->setParameter('conditionallyPassed', AsekuracyjnyReviewEquipment::RESULT_CONDITIONALLY_PASSED)
            ->getQuery()
            ->getSingleScalarResult();

        $setReviews = (clone $qb)
            ->select('COUNT(re.id)')
            ->andWhere('re.wasInSetAtReview = :wasInSet')
            ->setParameter('wasInSet', true)
            ->getQuery()
            ->getSingleScalarResult();

        $individualReviews = (clone $qb)
            ->select('COUNT(re.id)')
            ->andWhere('re.wasInSetAtReview = :wasInSet')
            ->setParameter('wasInSet', false)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'conditionally_passed' => $conditionallyPassed,
            'set_reviews' => $setReviews,
            'individual_reviews' => $individualReviews,
            'orphaned_reviews' => count($this->findOrphanedReviews())
        ];
    }

    /**
     * Find equipments reviewed in a specific time period
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('r.completedDate >= :startDate')
            ->andWhere('r.completedDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the latest review for each equipment
     */
    public function findLatestReviewForEquipments(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->leftJoin('re.equipment', 'e')
            ->where('r.status = :completedStatus')
            ->andWhere('re.equipment IS NOT NULL')
            ->setParameter('completedStatus', AsekuracyjnyReview::STATUS_COMPLETED)
            ->orderBy('e.id', 'ASC')
            ->addOrderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search in review equipment history
     */
    public function search(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->leftJoin('re.equipment', 'e')
            ->where('re.equipmentNameAtReview LIKE :query')
            ->orWhere('re.equipmentInventoryNumberAtReview LIKE :query')
            ->orWhere('re.equipmentSerialNumberAtReview LIKE :query')
            ->orWhere('e.name LIKE :query')
            ->orWhere('e.inventoryNumber LIKE :query')
            ->orWhere('r.reviewNumber LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('r.completedDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}