<?php

namespace App\AparaturaPomiarowa\Repository;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReviewEquipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AparaturaPomiarowaReviewEquipment>
 */
class AparaturaPomiarowaReviewEquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AparaturaPomiarowaReviewEquipment::class);
    }

    public function save(AparaturaPomiarowaReviewEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AparaturaPomiarowaReviewEquipment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByReview(int $reviewId): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->addSelect('e')
            ->where('re.review = :reviewId')
            ->setParameter('reviewId', $reviewId)
            ->orderBy('re.equipmentNameAtReview', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByEquipment(int $equipmentId): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->addSelect('r')
            ->where('re.equipment = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('r.completedDate', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByReviewAndEquipment(int $reviewId, int $equipmentId): ?AparaturaPomiarowaReviewEquipment
    {
        return $this->createQueryBuilder('re')
            ->where('re.review = :reviewId')
            ->andWhere('re.equipment = :equipmentId')
            ->setParameter('reviewId', $reviewId)
            ->setParameter('equipmentId', $equipmentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSetReviews(int $reviewId): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->addSelect('e')
            ->where('re.review = :reviewId')
            ->andWhere('re.wasInSetAtReview = :wasInSet')
            ->setParameter('reviewId', $reviewId)
            ->setParameter('wasInSet', true)
            ->orderBy('re.equipmentNameAtReview', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findIndividualReviews(int $reviewId): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->addSelect('e')
            ->where('re.review = :reviewId')
            ->andWhere('re.wasInSetAtReview = :wasInSet')
            ->setParameter('reviewId', $reviewId)
            ->setParameter('wasInSet', false)
            ->orderBy('re.equipmentNameAtReview', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithSpecificResult(string $result): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->leftJoin('re.review', 'r')
            ->addSelect('e')
            ->addSelect('r')
            ->where('re.individualResult = :result')
            ->setParameter('result', $result)
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFailedEquipment(): array
    {
        return $this->findWithSpecificResult(AparaturaPomiarowaReviewEquipment::RESULT_FAILED);
    }

    public function findConditionallyPassedEquipment(): array
    {
        return $this->findWithSpecificResult(AparaturaPomiarowaReviewEquipment::RESULT_CONDITIONALLY_PASSED);
    }

    public function getStatisticsByResult(): array
    {
        $results = $this->createQueryBuilder('re')
            ->select('re.individualResult as result, COUNT(re.id) as count')
            ->where('re.individualResult IS NOT NULL')
            ->andWhere('re.individualResult != :inherited')
            ->groupBy('re.individualResult')
            ->setParameter('inherited', AparaturaPomiarowaReviewEquipment::RESULT_INHERITED)
            ->getQuery()
            ->getResult();

        $statistics = [];
        foreach ($results as $result) {
            $statistics[$result['result']] = $result['count'];
        }

        return $statistics;
    }

    public function findEquipmentWithIndividualFindings(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->leftJoin('re.review', 'r')
            ->addSelect('e')
            ->addSelect('r')
            ->where('re.individualFindings IS NOT NULL')
            ->andWhere('re.individualFindings != :empty')
            ->setParameter('empty', '')
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findEquipmentWithRecommendations(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->leftJoin('re.review', 'r')
            ->addSelect('e')
            ->addSelect('r')
            ->where('re.individualRecommendations IS NOT NULL')
            ->andWhere('re.individualRecommendations != :empty')
            ->setParameter('empty', '')
            ->orderBy('r.completedDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOrphanedEquipmentReviews(): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.equipment', 'e')
            ->where('e.id IS NULL')
            ->orderBy('re.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countByReview(int $reviewId): int
    {
        return $this->createQueryBuilder('re')
            ->select('COUNT(re.id)')
            ->where('re.review = :reviewId')
            ->setParameter('reviewId', $reviewId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findLastReviewForEquipment(int $equipmentId): ?AparaturaPomiarowaReviewEquipment
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->where('re.equipment = :equipmentId')
            ->andWhere('r.status = :completedStatus')
            ->setParameter('equipmentId', $equipmentId)
            ->setParameter('completedStatus', 'completed')
            ->orderBy('r.completedDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findReviewHistoryForEquipment(int $equipmentId, int $limit = 10): array
    {
        return $this->createQueryBuilder('re')
            ->leftJoin('re.review', 'r')
            ->addSelect('r')
            ->where('re.equipment = :equipmentId')
            ->setParameter('equipmentId', $equipmentId)
            ->orderBy('r.completedDate', 'DESC')
            ->addOrderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}