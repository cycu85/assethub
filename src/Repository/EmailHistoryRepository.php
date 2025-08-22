<?php

namespace App\Repository;

use App\Entity\EmailHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailHistory>
 */
class EmailHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailHistory::class);
    }

    public function save(EmailHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailHistory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Usuń rekordy starsze niż określona liczba dni
     */
    public function deleteOlderThan(int $days): int
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$days} days");

        $qb = $this->createQueryBuilder('eh');
        $qb->delete()
           ->where('eh.sent_at < :cutoff_date')
           ->setParameter('cutoff_date', $cutoffDate);

        return $qb->getQuery()->execute();
    }

    /**
     * Znajdź wszystkie maile wysłane w ostatnich X dniach
     */
    public function findRecentEmails(int $days = 30): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$days} days");

        return $this->createQueryBuilder('eh')
            ->where('eh.sent_at >= :cutoff_date')
            ->setParameter('cutoff_date', $cutoffDate)
            ->orderBy('eh.sent_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statystyki wysłanych maili
     */
    public function getEmailStatistics(int $days = 30): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$days} days");

        $qb = $this->createQueryBuilder('eh');
        $qb->select('
                COUNT(eh.id) as total_emails,
                SUM(CASE WHEN eh.status = \'sent\' THEN 1 ELSE 0 END) as sent_emails,
                SUM(CASE WHEN eh.status = \'failed\' THEN 1 ELSE 0 END) as failed_emails,
                COUNT(DISTINCT eh.recipient_email) as unique_recipients
            ')
            ->where('eh.sent_at >= :cutoff_date')
            ->setParameter('cutoff_date', $cutoffDate);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (int) $result['total_emails'],
            'sent' => (int) $result['sent_emails'],
            'failed' => (int) $result['failed_emails'],
            'unique_recipients' => (int) $result['unique_recipients'],
            'success_rate' => $result['total_emails'] > 0 
                ? round(($result['sent_emails'] / $result['total_emails']) * 100, 2)
                : 0
        ];
    }

    /**
     * Znajdź maile dla określonego odbiorcy
     */
    public function findByRecipient(string $email, int $limit = 50): array
    {
        return $this->createQueryBuilder('eh')
            ->where('eh.recipient_email = :email')
            ->setParameter('email', $email)
            ->orderBy('eh.sent_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź maile według typu
     */
    public function findByType(string $emailType, int $limit = 100): array
    {
        return $this->createQueryBuilder('eh')
            ->where('eh.email_type = :email_type')
            ->setParameter('email_type', $emailType)
            ->orderBy('eh.sent_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź nieudane maile do powtórnego wysłania
     */
    public function findFailedEmails(int $hours = 24): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$hours} hours");

        return $this->createQueryBuilder('eh')
            ->where('eh.status = :status')
            ->andWhere('eh.sent_at >= :cutoff_date')
            ->setParameter('status', 'failed')
            ->setParameter('cutoff_date', $cutoffDate)
            ->orderBy('eh.sent_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Policz maile według statusu w ostatnich dniach
     */
    public function countByStatus(int $days = 7): array
    {
        $cutoffDate = new \DateTime();
        $cutoffDate->modify("-{$days} days");

        $qb = $this->createQueryBuilder('eh');
        $qb->select('eh.status, COUNT(eh.id) as count')
            ->where('eh.sent_at >= :cutoff_date')
            ->setParameter('cutoff_date', $cutoffDate)
            ->groupBy('eh.status')
            ->orderBy('count', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        $statusCounts = [];
        foreach ($results as $result) {
            $statusCounts[$result['status']] = (int) $result['count'];
        }

        return $statusCounts;
    }
}