<?php

namespace App\Service;

use App\Entity\EmailHistory;
use App\Entity\User;
use App\Repository\EmailHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bundle\SecurityBundle\Security;

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
            
            // Wyślij mail przez dynamiczny mailer
            $mailer = $this->createMailerFromSettings();
            $mailer->send($email);
            
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
            
            // Wyślij mail przez dynamiczny mailer
            $mailer = $this->createMailerFromSettings();
            $mailer->send($email);
            
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
     * Powiadomienie o przygotowanym przeglądzie zestawu - potrzeba dostarczenia na przegląd
     */
    public function sendEquipmentSetReviewPreparedEmail(string $recipientEmail, string $recipientName, array $reviewData): bool
    {
        $subject = 'Przygotowany przegląd zestawu asekuracyjnego - wymagane dostarczenie';
        
        // HTML body dla lepszego formatowania
        $htmlBody = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $htmlBody .= "<h3>Witaj {$recipientName}!</h3>";
        $htmlBody .= "<p>Informujemy, że został przygotowany przegląd dla przypisanego do Ciebie zestawu sprzętu asekuracyjnego.</p>";
        
        $htmlBody .= "<h4>SZCZEGÓŁY PRZEGLĄDU:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li><strong>Numer przeglądu:</strong> {$reviewData['review_number']}</li>";
        $htmlBody .= "<li><strong>Zestaw:</strong> {$reviewData['set_name']}</li>";
        $htmlBody .= "<li><strong>Typ przeglądu:</strong> {$reviewData['review_type']}</li>";
        $htmlBody .= "<li><strong>Planowana data przeglądu:</strong> {$reviewData['planned_date']}</li>";
        $htmlBody .= "<li><strong>Firma przeprowadzająca przegląd:</strong> {$reviewData['review_company']}</li>";
        
        if (!empty($reviewData['notes'])) {
            $htmlBody .= "<li><strong>Uwagi:</strong> {$reviewData['notes']}</li>";
        }
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<h4>ELEMENTY ZESTAWU DO DOSTARCZENIA:</h4>";
        if (!empty($reviewData['equipment_list'])) {
            $htmlBody .= "<ul>";
            foreach ($reviewData['equipment_list'] as $equipment) {
                $htmlBody .= "<li><strong>{$equipment['name']}</strong><br>";
                $htmlBody .= "&nbsp;&nbsp;• Nr inwentarzowy: {$equipment['inventory_number']}";
                if (!empty($equipment['serial_number'])) {
                    $htmlBody .= "<br>&nbsp;&nbsp;• Nr seryjny: {$equipment['serial_number']}";
                }
                $htmlBody .= "</li>";
            }
            $htmlBody .= "</ul>";
        }
        
        $htmlBody .= "<h4>WAŻNE INFORMACJE:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li>Prosimy o dostarczenie kompletnego zestawu na przegląd w wyznaczonym terminie</li>";
        $htmlBody .= "<li>Sprzęt powinien być czysty i gotowy do przeglądu technicznego</li>";
        $htmlBody .= "<li>W przypadku pytań lub problemów prosimy o kontakt z administratorem systemu</li>";
        $htmlBody .= "<li>Status przeglądu można śledzić w systemie AssetHub</li>";
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<p><strong>Pozdrawiamy,<br>";
        $htmlBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub') . "</strong></p>";
        $htmlBody .= "</body></html>";

        // Text fallback dla klientów nie obsługujących HTML
        $textBody = "Witaj {$recipientName}!\n\n";
        $textBody .= "Informujemy, że został przygotowany przegląd dla przypisanego do Ciebie zestawu sprzętu asekuracyjnego.\n\n";
        
        $textBody .= "SZCZEGÓŁY PRZEGLĄDU:\n";
        $textBody .= "• Numer przeglądu: {$reviewData['review_number']}\n";
        $textBody .= "• Zestaw: {$reviewData['set_name']}\n";
        $textBody .= "• Typ przeglądu: {$reviewData['review_type']}\n";
        $textBody .= "• Planowana data przeglądu: {$reviewData['planned_date']}\n";
        $textBody .= "• Firma przeprowadzająca przegląd: {$reviewData['review_company']}\n\n";
        
        if (!empty($reviewData['notes'])) {
            $textBody .= "• Uwagi: {$reviewData['notes']}\n\n";
        }
        
        $textBody .= "ELEMENTY ZESTAWU DO DOSTARCZENIA:\n";
        if (!empty($reviewData['equipment_list'])) {
            foreach ($reviewData['equipment_list'] as $equipment) {
                $textBody .= "• {$equipment['name']} (nr inwentarzowy: {$equipment['inventory_number']}";
                if (!empty($equipment['serial_number'])) {
                    $textBody .= ", nr seryjny: {$equipment['serial_number']}";
                }
                $textBody .= ")\n";
            }
        }
        $textBody .= "\n";
        
        $textBody .= "WAŻNE INFORMACJE:\n";
        $textBody .= "• Prosimy o dostarczenie kompletnego zestawu na przegląd w wyznaczonym terminie\n";
        $textBody .= "• Sprzęt powinien być czysty i gotowy do przeglądu technicznego\n";
        $textBody .= "• W przypadku pytań lub problemów prosimy o kontakt z administratorem systemu\n";
        $textBody .= "• Status przeglądu można śledzić w systemie AssetHub\n\n";
        
        $textBody .= "Pozdrawiamy,\n";
        $textBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->sendHtmlEmail(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $recipientName,
            'equipment_set_review_prepared',
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
        $fromEmail = $this->settingService->get('from_email', 'noreply@assethub.local');
        $fromName = $this->settingService->get('from_name', 'AssetHub System');
        
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
                
                $fromEmail = $this->settingService->get('from_email', 'noreply@assethub.local');
                $fromName = $this->settingService->get('from_name', 'AssetHub System');
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

    /**
     * Tworzy mailer z ustawień z bazy danych
     */
    private function createMailerFromSettings(): MailerInterface
    {
        // Pobierz ustawienia SMTP z bazy danych
        $host = $this->settingService->get('smtp_host', 'localhost');
        $port = $this->settingService->get('smtp_port', '25');
        $encryption = $this->settingService->get('smtp_encryption', 'none');
        $username = $this->settingService->get('smtp_username', '');
        $password = $this->settingService->get('smtp_password', '');

        // Sprawdź czy mamy kompletną konfigurację SMTP
        if (empty($host) || empty($username)) {
            $this->logger->warning('Incomplete SMTP configuration, falling back to default mailer', [
                'host' => $host,
                'username' => $username
            ]);
            return $this->mailer; // Użyj domyślnego mailera
        }

        // Buduj DSN na podstawie ustawień SMTP
        $dsnParts = [
            'smtp://',
            urlencode($username),
            ':',
            urlencode($password),
            '@',
            $host,
            ':',
            $port
        ];
        
        if ($encryption !== 'none') {
            $dsnParts[] = '?encryption=' . $encryption;
        }
        
        $dsn = implode('', $dsnParts);
        
        try {
            // Utwórz transport SMTP na podstawie konfiguracji
            $transport = Transport::fromDsn($dsn);
            return new Mailer($transport);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create SMTP transport from settings, falling back to default mailer', [
                'dsn' => preg_replace('/:[^:@]*@/', ':***@', $dsn), // Ukryj hasło w logu
                'error' => $e->getMessage()
            ]);
            return $this->mailer; // Użyj domyślnego mailera w razie błędu
        }
    }
}