<?php

namespace App\Repository;

use App\Entity\Dictionary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Dictionary>
 *
 * @method Dictionary|null find($id, $lockMode = null, $lockVersion = null)
 * @method Dictionary|null findOneBy(array $criteria, array $orderBy = null)
 * @method Dictionary[]    findAll()
 * @method Dictionary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DictionaryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dictionary::class);
    }

    public function save(Dictionary $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Dictionary $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find dictionaries by type
     */
    public function findByType(string $type, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->orderBy('d.sortOrder', 'ASC')
            ->addOrderBy('d.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('d.isActive = :active')
               ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find active dictionaries by type (alias for findByType with activeOnly = true)
     */
    public function findActiveByType(string $type): array
    {
        return $this->findByType($type, true);
    }

    /**
     * Find all dictionary types
     */
    public function findAllTypes(): array
    {
        // Use raw SQL to bypass potential ORM/cache issues
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT DISTINCT type FROM dictionaries ORDER BY type ASC';
        $result = $connection->executeQuery($sql);
        
        return $result->fetchFirstColumn();
    }

    /**
     * Find root level dictionaries (without parent)
     */
    public function findRootLevelByType(string $type, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->andWhere('d.parent IS NULL')
            ->setParameter('type', $type)
            ->orderBy('d.sortOrder', 'ASC')
            ->addOrderBy('d.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('d.isActive = :active')
               ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find dictionaries with their children
     */
    public function findWithChildrenByType(string $type, bool $activeOnly = true): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.children', 'c')
            ->addSelect('c')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->orderBy('d.sortOrder', 'ASC')
            ->addOrderBy('d.name', 'ASC')
            ->addOrderBy('c.sortOrder', 'ASC')
            ->addOrderBy('c.name', 'ASC');

        if ($activeOnly) {
            $qb->andWhere('d.isActive = :active')
               ->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count dictionaries by type
     */
    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find dictionary by type and value
     */
    public function findOneByTypeAndValue(string $type, string $value): ?Dictionary
    {
        return $this->createQueryBuilder('d')
            ->where('d.type = :type')
            ->andWhere('d.value = :value')
            ->setParameter('type', $type)
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }
}