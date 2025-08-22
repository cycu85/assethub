<?php

namespace App\EventSubscriber;

use App\Event\User\UserCreatedEvent;
use App\Event\User\UserUpdatedEvent;
use App\Service\AuditService;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ?EmailService $emailService = null
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::NAME => [
                ['onUserCreated', 10],  // High priority for critical logging
                ['sendWelcomeEmail', 5], // Lower priority for email
            ],
            UserUpdatedEvent::NAME => [
                ['onUserUpdated', 10],
                ['checkCriticalChanges', 5],
            ],
        ];
    }

    public function onUserCreated(UserCreatedEvent $event): void
    {
        $user = $event->getUser();
        $createdBy = $event->getCreatedBy();

        // Detailed audit logging
        $this->auditService->logUserAction($createdBy, 'user_created_event', [
            'created_user_id' => $user->getId(),
            'created_user_username' => $user->getUsername(),
            'created_user_email' => $user->getEmail(),
            'is_ldap_user' => $user->isLdapUser(),
            'context' => $event->getContext()
        ]);

        // System-wide logging
        $this->logger->info('User created via event system', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'created_by' => $createdBy->getUsername(),
            'event_context' => $event->getContext()
        ]);
    }

    public function sendWelcomeEmail(UserCreatedEvent $event): void
    {
        if (!$this->emailService) {
            return; // Email service not configured
        }

        $user = $event->getUser();
        $context = $event->getContext();

        // Sprawdź czy wysłać mail z hasłem tymczasowym
        $temporaryPassword = $context['temporary_password'] ?? null;

        $success = $this->emailService->sendWelcomeEmail($user, $temporaryPassword);

        if ($success) {
            $this->logger->info('Welcome email sent via EmailService', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'temporary_password_included' => $temporaryPassword !== null
            ]);
        } else {
            $this->logger->error('Failed to send welcome email via EmailService', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);
        }
    }

    public function onUserUpdated(UserUpdatedEvent $event): void
    {
        $user = $event->getUser();
        $updatedBy = $event->getUpdatedBy();
        $changes = $event->getChanges();

        // Detailed audit logging
        $this->auditService->logUserAction($updatedBy, 'user_updated_event', [
            'updated_user_id' => $user->getId(),
            'updated_user_username' => $user->getUsername(),
            'changes' => $changes,
            'context' => $event->getContext()
        ]);

        // Log significant changes
        if (isset($changes['isActive'])) {
            $this->logger->warning('User active status changed', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'old_status' => $changes['isActive']['from'],
                'new_status' => $changes['isActive']['to'],
                'updated_by' => $updatedBy->getUsername()
            ]);
        }
    }

    public function checkCriticalChanges(UserUpdatedEvent $event): void
    {
        $changes = $event->getChanges();
        $user = $event->getUser();

        $criticalFields = ['email', 'isActive', 'password'];
        $criticalChanges = array_intersect_key($changes, array_flip($criticalFields));

        if (!empty($criticalChanges)) {
            $this->auditService->logSecurityEvent('user_critical_changes', $event->getUpdatedBy(), [
                'affected_user_id' => $user->getId(),
                'affected_username' => $user->getUsername(),
                'critical_changes' => $criticalChanges
            ]);
        }
    }
}