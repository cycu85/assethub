<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Create and save a notification
     */
    public function createNotification(
        User $user,
        string $title,
        string $content,
        string $type = Notification::TYPE_INFO,
        string $category = Notification::CATEGORY_SYSTEM,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): Notification {
        $notification = new Notification();
        $notification
            ->setUser($user)
            ->setTitle($title)
            ->setContent($content)
            ->setType($type)
            ->setCategory($category)
            ->setData($data ?? [])
            ->setActionUrl($actionUrl)
            ->setActionText($actionText);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->logger->info('Notification created', [
            'user_id' => $user->getId(),
            'title' => $title,
            'type' => $type,
            'category' => $category
        ]);

        return $notification;
    }

    /**
     * Create review notification for equipment set
     */
    public function createReviewNotification(User $user, string $equipmentSetName, int $equipmentSetId): Notification
    {
        return $this->createNotification(
            user: $user,
            title: 'Przegląd zestawu przygotowany',
            content: sprintf('Przegląd zestawu "%s" został przygotowany i oczekuje na dostarczenie.', $equipmentSetName),
            type: Notification::TYPE_WARNING,
            category: Notification::CATEGORY_REVIEW,
            data: [
                'equipment_set_id' => $equipmentSetId,
                'equipment_set_name' => $equipmentSetName
            ],
            actionUrl: "/asekuracja/equipment-set/{$equipmentSetId}",
            actionText: 'Zobacz szczegóły'
        );
    }

    /**
     * Create review notification for individual equipment
     */
    public function createEquipmentReviewNotification(User $user, string $equipmentName, int $equipmentId): Notification
    {
        return $this->createNotification(
            user: $user,
            title: 'Przegląd sprzętu przygotowany',
            content: sprintf('Przegląd sprzętu "%s" został przygotowany i oczekuje na dostarczenie.', $equipmentName),
            type: Notification::TYPE_WARNING,
            category: Notification::CATEGORY_REVIEW,
            data: [
                'equipment_id' => $equipmentId,
                'equipment_name' => $equipmentName
            ],
            actionUrl: "/asekuracja/equipment/{$equipmentId}",
            actionText: 'Zobacz szczegóły'
        );
    }

    /**
     * Create transfer notification
     */
    public function createTransferNotification(User $user, string $equipmentSetName, int $equipmentSetId, string $transferType = 'transfer'): Notification
    {
        $titles = [
            'transfer' => 'Nowe przekazanie sprzętu',
            'return' => 'Zwrot sprzętu'
        ];

        $contents = [
            'transfer' => sprintf('Otrzymałeś zestaw sprzętu "%s" do przekazania.', $equipmentSetName),
            'return' => sprintf('Zestaw sprzętu "%s" został zwrócony.', $equipmentSetName)
        ];

        return $this->createNotification(
            user: $user,
            title: $titles[$transferType] ?? $titles['transfer'],
            content: $contents[$transferType] ?? $contents['transfer'],
            type: Notification::TYPE_INFO,
            category: Notification::CATEGORY_TRANSFER,
            data: [
                'equipment_set_id' => $equipmentSetId,
                'equipment_set_name' => $equipmentSetName,
                'transfer_type' => $transferType
            ],
            actionUrl: "/asekuracja/equipment-set/{$equipmentSetId}",
            actionText: 'Zobacz szczegóły'
        );
    }

    /**
     * Create equipment notification
     */
    public function createEquipmentNotification(User $user, string $title, string $content, ?array $data = null): Notification
    {
        return $this->createNotification(
            user: $user,
            title: $title,
            content: $content,
            type: Notification::TYPE_INFO,
            category: Notification::CATEGORY_EQUIPMENT,
            data: $data
        );
    }

    /**
     * Create system notification
     */
    public function createSystemNotification(User $user, string $title, string $content, string $type = Notification::TYPE_INFO): Notification
    {
        return $this->createNotification(
            user: $user,
            title: $title,
            content: $content,
            type: $type,
            category: Notification::CATEGORY_SYSTEM
        );
    }

    /**
     * Notify multiple users
     */
    public function notifyMultipleUsers(
        array $users,
        string $title,
        string $content,
        string $type = Notification::TYPE_INFO,
        string $category = Notification::CATEGORY_SYSTEM,
        ?array $data = null,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): array {
        $notifications = [];

        foreach ($users as $user) {
            $notifications[] = $this->createNotification(
                $user, $title, $content, $type, $category, $data, $actionUrl, $actionText
            );
        }

        return $notifications;
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications(User $user, bool $unreadOnly = false, int $limit = 20): array
    {
        return $this->notificationRepository->findByUser($user, $unreadOnly, $limit);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount(User $user): int
    {
        return $this->notificationRepository->countUnreadByUser($user);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
        $this->entityManager->flush();
    }

    /**
     * Mark multiple notifications as read
     */
    public function markMultipleAsRead(array $notifications): void
    {
        foreach ($notifications as $notification) {
            $notification->markAsRead();
        }
        $this->entityManager->flush();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsReadForUser($user);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(Notification $notification): void
    {
        $this->entityManager->remove($notification);
        $this->entityManager->flush();
    }

    /**
     * Delete multiple notifications
     */
    public function deleteMultipleNotifications(array $notifications): void
    {
        foreach ($notifications as $notification) {
            $this->entityManager->remove($notification);
        }
        $this->entityManager->flush();
    }

    /**
     * Clean old notifications
     */
    public function cleanOldNotifications(int $days = 30): int
    {
        $deleted = $this->notificationRepository->deleteOldNotifications($days);
        
        $this->logger->info('Old notifications cleaned', [
            'days' => $days,
            'deleted_count' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Get user notification statistics
     */
    public function getUserStats(User $user): array
    {
        return $this->notificationRepository->getUserNotificationStats($user);
    }

    /**
     * Get notifications grouped by category
     */
    public function getNotificationsGroupedByCategory(User $user, int $limit = 20): array
    {
        return $this->notificationRepository->findRecentGroupedByCategory($user, $limit);
    }

    /**
     * Get notifications by category
     */
    public function getNotificationsByCategory(User $user, string $category, int $limit = 10): array
    {
        return $this->notificationRepository->findByUserAndCategory($user, $category, $limit);
    }
}