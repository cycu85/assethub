<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find notifications for a user
     */
    public function findByUser(User $user, bool $unreadOnly = false, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC');

        if ($unreadOnly) {
            $qb->andWhere('n.isRead = false');
        }

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count unread notifications for a user
     */
    public function countUnreadByUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find notifications by category for a user
     */
    public function findByUserAndCategory(User $user, string $category, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->andWhere('n.category = :category')
            ->setParameter('user', $user)
            ->setParameter('category', $category)
            ->orderBy('n.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsReadForUser(User $user): int
    {
        return $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->set('n.readAt', ':now')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Delete old notifications (older than specified days)
     */
    public function deleteOldNotifications(int $days = 30): int
    {
        $date = new \DateTimeImmutable('-' . $days . ' days');

        return $this->createQueryBuilder('n')
            ->delete()
            ->where('n.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Find recent notifications grouped by category
     */
    public function findRecentGroupedByCategory(User $user, int $limit = 20): array
    {
        $notifications = $this->findByUser($user, false, $limit);
        
        $grouped = [];
        foreach ($notifications as $notification) {
            $category = $notification->getCategory();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $notification;
        }

        return $grouped;
    }

    /**
     * Get notification statistics for a user
     */
    public function getUserNotificationStats(User $user): array
    {
        $result = $this->createQueryBuilder('n')
            ->select('n.category, n.type, COUNT(n.id) as count, SUM(CASE WHEN n.isRead = false THEN 1 ELSE 0 END) as unread_count')
            ->where('n.user = :user')
            ->andWhere('n.createdAt >= :since')
            ->setParameter('user', $user)
            ->setParameter('since', new \DateTimeImmutable('-30 days'))
            ->groupBy('n.category, n.type')
            ->getQuery()
            ->getResult();

        $stats = [
            'total' => 0,
            'unread' => 0,
            'by_category' => [],
            'by_type' => []
        ];

        foreach ($result as $row) {
            $stats['total'] += $row['count'];
            $stats['unread'] += $row['unread_count'];
            
            $category = $row['category'];
            $type = $row['type'];
            
            if (!isset($stats['by_category'][$category])) {
                $stats['by_category'][$category] = ['total' => 0, 'unread' => 0];
            }
            if (!isset($stats['by_type'][$type])) {
                $stats['by_type'][$type] = ['total' => 0, 'unread' => 0];
            }
            
            $stats['by_category'][$category]['total'] += $row['count'];
            $stats['by_category'][$category]['unread'] += $row['unread_count'];
            $stats['by_type'][$type]['total'] += $row['count'];
            $stats['by_type'][$type]['unread'] += $row['unread_count'];
        }

        return $stats;
    }
}