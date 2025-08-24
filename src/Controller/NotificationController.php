<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get current user's notifications
     */
    #[Route('', name: 'api_notifications_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'error' => 'User not authenticated',
                'notifications' => [],
                'unread_count' => 0,
                'total' => 0
            ], 401);
        }
        
        $unreadOnly = $request->query->getBoolean('unread_only', false);
        $limit = min($request->query->getInt('limit', 20), 100);
        $category = $request->query->get('category');

        if ($category) {
            $notifications = $this->notificationService->getNotificationsByCategory($user, $category, $limit);
        } else {
            $notifications = $this->notificationService->getUserNotifications($user, $unreadOnly, $limit);
        }

        $data = array_map(function (Notification $notification) {
            return $this->formatNotification($notification);
        }, $notifications);

        return $this->json([
            'notifications' => $data,
            'unread_count' => $this->notificationService->getUnreadCount($user),
            'total' => count($data)
        ]);
    }

    /**
     * Get notifications count
     */
    #[Route('/count', name: 'api_notifications_count', methods: ['GET'])]
    public function count(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'error' => 'User not authenticated',
                'unread_count' => 0
            ], 401);
        }
        
        $unreadCount = $this->notificationService->getUnreadCount($user);

        return $this->json([
            'unread_count' => $unreadCount,
            'user_id' => $user->getId(), // Debug info
            'user_name' => $user->getUsername() // Debug info
        ]);
    }

    /**
     * Get user notification statistics
     */
    #[Route('/stats', name: 'api_notifications_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $user = $this->getUser();
        $stats = $this->notificationService->getUserStats($user);

        return $this->json($stats);
    }

    /**
     * Mark notification as read
     */
    #[Route('/{id}/read', name: 'api_notifications_mark_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->notificationService->markAsRead($notification);

        return $this->json([
            'success' => true,
            'notification' => $this->formatNotification($notification)
        ]);
    }

    /**
     * Mark notification as unread
     */
    #[Route('/{id}/unread', name: 'api_notifications_mark_unread', methods: ['POST'])]
    public function markAsUnread(Notification $notification): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $notification->markAsUnread();
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'notification' => $this->formatNotification($notification)
        ]);
    }

    /**
     * Mark all notifications as read
     */
    #[Route('/mark-all-read', name: 'api_notifications_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user = $this->getUser();
        $markedCount = $this->notificationService->markAllAsRead($user);

        return $this->json([
            'success' => true,
            'marked_count' => $markedCount,
            'unread_count' => 0
        ]);
    }

    /**
     * Mark multiple notifications as read
     */
    #[Route('/mark-multiple-read', name: 'api_notifications_mark_multiple_read', methods: ['POST'])]
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $notificationIds = $data['notification_ids'] ?? [];

        if (empty($notificationIds)) {
            return $this->json(['error' => 'No notifications specified'], Response::HTTP_BAD_REQUEST);
        }

        $notifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['id' => $notificationIds, 'user' => $user]);

        $this->notificationService->markMultipleAsRead($notifications);

        return $this->json([
            'success' => true,
            'marked_count' => count($notifications),
            'unread_count' => $this->notificationService->getUnreadCount($user)
        ]);
    }

    /**
     * Delete notification
     */
    #[Route('/{id}', name: 'api_notifications_delete', methods: ['DELETE'])]
    public function delete(Notification $notification): JsonResponse
    {
        if ($notification->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->notificationService->deleteNotification($notification);

        return $this->json(['success' => true]);
    }

    /**
     * Delete multiple notifications
     */
    #[Route('/delete-multiple', name: 'api_notifications_delete_multiple', methods: ['POST'])]
    public function deleteMultiple(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $notificationIds = $data['notification_ids'] ?? [];

        if (empty($notificationIds)) {
            return $this->json(['error' => 'No notifications specified'], Response::HTTP_BAD_REQUEST);
        }

        $notifications = $this->entityManager->getRepository(Notification::class)
            ->findBy(['id' => $notificationIds, 'user' => $user]);

        $this->notificationService->deleteMultipleNotifications($notifications);

        return $this->json([
            'success' => true,
            'deleted_count' => count($notifications)
        ]);
    }

    /**
     * Get notifications grouped by category
     */
    #[Route('/grouped', name: 'api_notifications_grouped', methods: ['GET'])]
    public function grouped(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $limit = min($request->query->getInt('limit', 20), 100);
        
        $grouped = $this->notificationService->getNotificationsGroupedByCategory($user, $limit);

        $formattedGroups = [];
        foreach ($grouped as $category => $notifications) {
            $formattedGroups[$category] = array_map(function (Notification $notification) {
                return $this->formatNotification($notification);
            }, $notifications);
        }

        return $this->json([
            'grouped_notifications' => $formattedGroups,
            'unread_count' => $this->notificationService->getUnreadCount($user)
        ]);
    }

    /**
     * Format notification for API response
     */
    private function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'title' => $notification->getTitle(),
            'content' => $notification->getContent(),
            'type' => $notification->getType(),
            'category' => $notification->getCategory(),
            'is_read' => $notification->isRead(),
            'created_at' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            'time_ago' => $notification->getTimeAgo(),
            'action_url' => $notification->getActionUrl(),
            'action_text' => $notification->getActionText(),
            'data' => $notification->getData(),
            'type_icon' => $notification->getTypeIcon(),
            'type_color' => $notification->getTypeColor(),
            'category_icon' => $notification->getCategoryIcon()
        ];
    }
}