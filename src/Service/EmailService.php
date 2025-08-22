<?php

namespace App\Service;

use App\Entity\EmailHistory;
use App\Entity\User;
use App\Repository\EmailHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\Security;

class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager,
        private EmailHistoryRepository $emailHistoryRepository,
        private LoggerInterface $logger,
        private SettingService $settingService,
        private Security $security
    ) {
    }

    /**
     * Wysłanie maila z automatycznym logowaniem do historii
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $body,
        ?string $toName = null,
        ?string $emailType = null,
        array $metadata = []
    ): bool {
        try {
            $email = $this->createEmail($to, $subject, $body, $toName);
            
            // Wyślij mail
            $this->mailer->send($email);
            
            // Zaloguj do historii jako udany
            $this->logEmailToHistory($email, 'sent', null, $emailType, $metadata);
            
            $this->logger->info('Email sent successfully', [
                'recipient' => $to,
                'subject' => $subject,
                'type' => $emailType
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            // Zaloguj błąd do historii
            $this->logEmailToHistory($email ?? null, 'failed', $e->getMessage(), $emailType, $metadata, $to, $subject, $body, $toName);
            
            $this->logger->error('Failed to send email', [
                'recipient' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'type' => $emailType
            ]);
            
            return false;
        }
    }

    /**
     * Wysłanie maila HTML z automatycznym logowaniem
     */
    public function sendHtmlEmail(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $toName = null,
        ?string $emailType = null,
        array $metadata = []
    ): bool {
        try {
            $email = $this->createEmail($to, $subject, $textBody ?? strip_tags($htmlBody), $toName);
            $email->html($htmlBody);
            
            // Wyślij mail
            $this->mailer->send($email);
            
            // Zaloguj do historii jako udany
            $this->logEmailToHistory($email, 'sent', null, $emailType, $metadata);
            
            $this->logger->info('HTML email sent successfully', [
                'recipient' => $to,
                'subject' => $subject,
                'type' => $emailType
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            // Zaloguj błąd do historii
            $this->logEmailToHistory($email ?? null, 'failed', $e->getMessage(), $emailType, $metadata, $to, $subject, $htmlBody, $toName);
            
            $this->logger->error('Failed to send HTML email', [
                'recipient' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'type' => $emailType
            ]);
            
            return false;
        }
    }

    /**
     * Wyślij mail powitalny dla nowego użytkownika
     */
    public function sendWelcomeEmail(User $user, ?string $temporaryPassword = null): bool
    {
        $subject = 'Witamy w systemie ' . $this->settingService->get('app_name', 'AssetHub');
        
        $body = "Witaj {$user->getFirstName()}!\n\n";
        $body .= "Twoje konto w systemie zostało utworzone.\n";
        $body .= "Nazwa użytkownika: {$user->getUsername()}\n";
        
        if ($temporaryPassword) {
            $body .= "Hasło tymczasowe: {$temporaryPassword}\n";
            $body .= "Pamiętaj o zmianie hasła po pierwszym logowaniu.\n";
        }
        
        $body .= "\nMożesz teraz zalogować się do systemu.\n";
        $body .= "Pozdrawiamy,\nZespół " . $this->settingService->get('app_name', 'AssetHub');

        $metadata = [
            'user_id' => $user->getId(),
            'user_username' => $user->getUsername(),
            'temporary_password_sent' => $temporaryPassword !== null
        ];

        return $this->sendEmail(
            $user->getEmail(),
            $subject,
            $body,
            $user->getFullName(),
            'user_welcome',
            $metadata
        );
    }

    /**
     * Wyślij mail z resetem hasła
     */
    public function sendPasswordResetEmail(User $user, string $resetToken): bool
    {
        $subject = 'Reset hasła - ' . $this->settingService->get('app_name', 'AssetHub');
        
        $body = "Witaj {$user->getFirstName()}!\n\n";
        $body .= "Otrzymałeś ten mail, ponieważ ktoś poprosił o reset hasła dla Twojego konta.\n";
        $body .= "Token do resetu hasła: {$resetToken}\n";
        $body .= "Jeśli nie prosiłeś o reset hasła, zignoruj ten mail.\n\n";
        $body .= "Pozdrawiamy,\nZespół " . $this->settingService->get('app_name', 'AssetHub');

        $metadata = [
            'user_id' => $user->getId(),
            'user_username' => $user->getUsername(),
            'reset_token' => substr($resetToken, 0, 10) . '...' // Tylko część tokenu dla bezpieczeństwa
        ];

        return $this->sendEmail(
            $user->getEmail(),
            $subject,
            $body,
            $user->getFullName(),
            'password_reset',
            $metadata
        );
    }

    /**
     * Wyślij mail z powiadomieniem o przegladzie asekuracyjnym
     */
    public function sendReviewNotificationEmail(string $recipientEmail, string $recipientName, array $reviewData): bool
    {
        $subject = 'Powiadomienie o przeglądzie sprzętu asekuracyjnego';
        
        $body = "Witaj {$recipientName}!\n\n";
        $body .= "Informujemy o statusie przeglądu sprzętu asekuracyjnego:\n";
        $body .= "Numer przeglądu: {$reviewData['review_number']}\n";
        $body .= "Status: {$reviewData['status']}\n";
        $body .= "Data: {$reviewData['date']}\n\n";
        
        if (isset($reviewData['equipment_name'])) {
            $body .= "Sprzęt: {$reviewData['equipment_name']}\n";
        }
        
        $body .= "\nPozdrawiamy,\nZespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->sendEmail(
            $recipientEmail,
            $subject,
            $body,
            $recipientName,
            'review_notification',
            $reviewData
        );
    }

    /**
     * Przeczyść stare rekordy historii maili
     */
    public function cleanupOldEmails(?int $daysToKeep = null): int
    {
        $daysToKeep = $daysToKeep ?? (int) $this->settingService->get('email_history_retention_days', 90);
        
        $deletedCount = $this->emailHistoryRepository->deleteOlderThan($daysToKeep);
        
        $this->logger->info('Email history cleanup completed', [
            'days_to_keep' => $daysToKeep,
            'deleted_records' => $deletedCount
        ]);
        
        return $deletedCount;
    }

    /**
     * Pobierz statystyki maili
     */
    public function getEmailStatistics(int $days = 30): array
    {
        return $this->emailHistoryRepository->getEmailStatistics($days);
    }

    /**
     * Pobierz historię maili dla odbiorcy
     */
    public function getEmailHistoryForRecipient(string $email, int $limit = 50): array
    {
        return $this->emailHistoryRepository->findByRecipient($email, $limit);
    }

    /**
     * Tworzy obiekt Email
     */
    private function createEmail(string $to, string $subject, string $body, ?string $toName = null): Email
    {
        $fromEmail = $this->settingService->get('mail_from_address', 'noreply@assethub.local');
        $fromName = $this->settingService->get('mail_from_name', 'AssetHub System');
        
        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->subject($subject)
            ->text($body);
            
        if ($toName) {
            $email->to(new Address($to, $toName));
        } else {
            $email->to($to);
        }
        
        return $email;
    }

    /**
     * Loguje mail do historii
     */
    private function logEmailToHistory(
        ?Email $email,
        string $status,
        ?string $errorMessage = null,
        ?string $emailType = null,
        array $metadata = [],
        ?string $fallbackTo = null,
        ?string $fallbackSubject = null,
        ?string $fallbackBody = null,
        ?string $fallbackToName = null
    ): void {
        try {
            $history = new EmailHistory();
            
            // Ustaw dane z obiektu Email lub z fallbacków
            if ($email) {
                $to = $email->getTo()[0] ?? null;
                $from = $email->getFrom()[0] ?? null;
                
                $history->setRecipientEmail($to?->getAddress() ?? $fallbackTo ?? 'unknown@unknown.com');
                $history->setRecipientName($to?->getName() ?? $fallbackToName);
                $history->setSubject($email->getSubject() ?? $fallbackSubject ?? 'No subject');
                $history->setBodyText($email->getTextBody() ?? $fallbackBody);
                $history->setBodyHtml($email->getHtmlBody());
                $history->setSenderEmail($from?->getAddress());
                $history->setSenderName($from?->getName());
            } else {
                // Fallback gdy nie ma obiektu Email (błąd przed utworzeniem)
                $history->setRecipientEmail($fallbackTo ?? 'unknown@unknown.com');
                $history->setRecipientName($fallbackToName);
                $history->setSubject($fallbackSubject ?? 'No subject');
                $history->setBodyText($fallbackBody);
                
                $fromEmail = $this->settingService->get('mail_from_address', 'noreply@assethub.local');
                $fromName = $this->settingService->get('mail_from_name', 'AssetHub System');
                $history->setSenderEmail($fromEmail);
                $history->setSenderName($fromName);
            }
            
            $history->setSentAt(new \DateTime());
            $history->setStatus($status);
            $history->setErrorMessage($errorMessage);
            $history->setEmailType($emailType);
            $history->setMetadata($metadata);
            
            // Ustaw użytkownika który wysłał mail
            $currentUser = $this->security->getUser();
            if ($currentUser instanceof User) {
                $history->setSentBy($currentUser);
            }
            
            $this->entityManager->persist($history);
            $this->entityManager->flush();
            
        } catch (\Exception $e) {
            // Nie przerywamy działania aplikacji jeśli logowanie się nie uda
            $this->logger->error('Failed to log email to history', [
                'error' => $e->getMessage(),
                'recipient' => $fallbackTo ?? 'unknown'
            ]);
        }
    }
}