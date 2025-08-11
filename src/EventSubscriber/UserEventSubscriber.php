<?php

namespace App\EventSubscriber;

use App\Event\User\UserCreatedEvent;
use App\Event\User\UserUpdatedEvent;
use App\Service\AuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditService $auditService,
        private LoggerInterface $logger,
        private ?MailerInterface $mailer = null
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
        if (!$this->mailer) {
            return; // Mailer not configured
        }

        $user = $event->getUser();

        try {
            $email = (new Email())
                ->from('noreply@assethub.local')
                ->to($user->getEmail())
                ->subject('Witamy w AssetHub!')
                ->html($this->getWelcomeEmailContent($user));

            $this->mailer->send($email);

            $this->logger->info('Welcome email sent', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to send welcome email', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'error' => $e->getMessage()
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

    private function getWelcomeEmailContent($user): string
    {
        return sprintf(
            '<h1>Witamy w AssetHub, %s!</h1>
            <p>Twoje konto zostało utworzone pomyślnie.</p>
            <p><strong>Nazwa użytkownika:</strong> %s</p>
            <p><strong>Email:</strong> %s</p>
            <p>Możesz teraz zalogować się do systemu zarządzania zasobami.</p>
            <p>Zespół AssetHub</p>',
            htmlspecialchars($user->getFullName()),
            htmlspecialchars($user->getUsername()),
            htmlspecialchars($user->getEmail())
        );
    }
}