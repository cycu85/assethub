<?php

namespace App\AparaturaPomiarowa\Service;

use App\Entity\User;
use App\Service\EmailService;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\Service\SettingService;
use Psr\Log\LoggerInterface;

class AparaturaPomiarowaEmailService
{
    public function __construct(
        private EmailService $emailService,
        private SettingService $settingService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Wyślij powiadomienie o kalibracji aparatury pomiarowej
     */
    public function sendReviewNotificationEmail(string $recipientEmail, string $recipientName, array $reviewData): bool
    {
        $subject = 'Powiadomienie o kalibracji aparatury pomiarowej';
        
        $body = "Witaj {$recipientName}!\n\n";
        $body .= "Informujemy o statusie kalibracji aparatury pomiarowej:\n";
        $body .= "Numer kalibracji: {$reviewData['review_number']}\n";
        $body .= "Status: {$reviewData['status']}\n";
        $body .= "Data: {$reviewData['date']}\n\n";
        
        if (isset($reviewData['equipment_name'])) {
            $body .= "Urządzenie: {$reviewData['equipment_name']}\n";
        }
        
        $body .= "\nPozdrawiamy,\nZespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->emailService->sendEmail(
            $recipientEmail,
            $subject,
            $body,
            $recipientName,
            'aparatura_review_notification',
            $reviewData
        );
    }

    /**
     * Powiadomienie o przygotowanej kalibracji zestawu - potrzeba dostarczenia na kalibrację
     */
    public function sendEquipmentSetReviewPreparedEmail(string $recipientEmail, string $recipientName, array $reviewData): bool
    {
        $subject = 'Przygotowana kalibracja aparatury pomiarowej - wymagane dostarczenie';
        
        // HTML body dla lepszego formatowania
        $htmlBody = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $htmlBody .= "<h3>Witaj {$recipientName}!</h3>";
        $htmlBody .= "<p>Informujemy, że została przygotowana kalibracja dla przypisanego do Ciebie zestawu aparatury pomiarowej.</p>";
        
        $htmlBody .= "<h4>SZCZEGÓŁY KALIBRACJI:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li><strong>Numer kalibracji:</strong> {$reviewData['review_number']}</li>";
        $htmlBody .= "<li><strong>Zestaw:</strong> {$reviewData['set_name']}</li>";
        $htmlBody .= "<li><strong>Typ kalibracji:</strong> {$reviewData['review_type']}</li>";
        $htmlBody .= "<li><strong>Planowana data kalibracji:</strong> {$reviewData['planned_date']}</li>";
        $htmlBody .= "<li><strong>Laboratorium kalibracyjne:</strong> {$reviewData['review_company']}</li>";
        
        if (!empty($reviewData['notes'])) {
            $htmlBody .= "<li><strong>Uwagi:</strong> {$reviewData['notes']}</li>";
        }
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<h4>APARATURA DO DOSTARCZENIA NA KALIBRACJĘ:</h4>";
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
        $htmlBody .= "<li>Prosimy o dostarczenie kompletnego zestawu na kalibrację w wyznaczonym terminie</li>";
        $htmlBody .= "<li>Aparatura powinna być czysta i gotowa do badań kalibracyjnych</li>";
        $htmlBody .= "<li>Dołączyć należy protokoły z poprzednich kalibracji (jeśli dostępne)</li>";
        $htmlBody .= "<li>W przypadku pytań lub problemów prosimy o kontakt z administratorem systemu</li>";
        $htmlBody .= "<li>Status kalibracji można śledzić w systemie AssetHub</li>";
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<p><strong>Pozdrawiamy,<br>";
        $htmlBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub') . "</strong></p>";
        $htmlBody .= "</body></html>";

        // Text fallback dla klientów nie obsługujących HTML
        $textBody = "Witaj {$recipientName}!\n\n";
        $textBody .= "Informujemy, że została przygotowana kalibracja dla przypisanego do Ciebie zestawu aparatury pomiarowej.\n\n";
        
        $textBody .= "SZCZEGÓŁY KALIBRACJI:\n";
        $textBody .= "• Numer kalibracji: {$reviewData['review_number']}\n";
        $textBody .= "• Zestaw: {$reviewData['set_name']}\n";
        $textBody .= "• Typ kalibracji: {$reviewData['review_type']}\n";
        $textBody .= "• Planowana data kalibracji: {$reviewData['planned_date']}\n";
        $textBody .= "• Laboratorium kalibracyjne: {$reviewData['review_company']}\n\n";
        
        if (!empty($reviewData['notes'])) {
            $textBody .= "• Uwagi: {$reviewData['notes']}\n\n";
        }
        
        $textBody .= "APARATURA DO DOSTARCZENIA NA KALIBRACJĘ:\n";
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
        $textBody .= "• Prosimy o dostarczenie kompletnego zestawu na kalibrację w wyznaczonym terminie\n";
        $textBody .= "• Aparatura powinna być czysta i gotowa do badań kalibracyjnych\n";
        $textBody .= "• Dołączyć należy protokoły z poprzednich kalibracji (jeśli dostępne)\n";
        $textBody .= "• W przypadku pytań lub problemów prosimy o kontakt z administratorem systemu\n";
        $textBody .= "• Status kalibracji można śledzić w systemie AssetHub\n\n";
        
        $textBody .= "Pozdrawiamy,\n";
        $textBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->emailService->sendHtmlEmail(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $recipientName,
            'aparatura_set_review_prepared',
            $reviewData
        );
    }

    /**
     * Wyślij powiadomienie o przekroczeniu terminu kalibracji
     */
    public function sendOverdueCalibrationEmail(string $recipientEmail, string $recipientName, array $equipmentData): bool
    {
        $subject = 'Przekroczenie terminu kalibracji aparatury pomiarowej';
        
        $htmlBody = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6; color: #d32f2f;'>";
        $htmlBody .= "<h3 style='color: #d32f2f;'>UWAGA: Przekroczenie terminu kalibracji!</h3>";
        $htmlBody .= "<p><strong>Witaj {$recipientName}!</strong></p>";
        $htmlBody .= "<p style='color: #d32f2f;'>Informujemy, że następująca aparatura pomiarowa przekroczyła termin kalibracji:</p>";
        
        $htmlBody .= "<div style='background-color: #ffebee; padding: 15px; border-left: 4px solid #f44336; margin: 10px 0;'>";
        $htmlBody .= "<h4 style='margin-top: 0; color: #d32f2f;'>APARATURA WYMAGAJĄCA NATYCHMIASTOWEJ KALIBRACJI:</h4>";
        $htmlBody .= "<ul style='margin-bottom: 0;'>";
        
        foreach ($equipmentData['equipment'] as $equipment) {
            $htmlBody .= "<li><strong>{$equipment['name']}</strong><br>";
            $htmlBody .= "&nbsp;&nbsp;• Nr inwentarzowy: {$equipment['inventory_number']}<br>";
            $htmlBody .= "&nbsp;&nbsp;• Termin kalibracji: <strong style='color: #d32f2f;'>{$equipment['calibration_due']}</strong><br>";
            $htmlBody .= "&nbsp;&nbsp;• Dni po terminie: <strong style='color: #d32f2f;'>{$equipment['days_overdue']}</strong><br>";
            if (!empty($equipment['location'])) {
                $htmlBody .= "&nbsp;&nbsp;• Lokalizacja: {$equipment['location']}<br>";
            }
            $htmlBody .= "</li>";
        }
        $htmlBody .= "</ul>";
        $htmlBody .= "</div>";
        
        $htmlBody .= "<h4 style='color: #d32f2f;'>WYMAGANE DZIAŁANIA:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li><strong>NATYCHMIAST</strong> wyłączyć aparaturę z użytkowania</li>";
        $htmlBody .= "<li>Skontaktować się z laboratorium kalibracyjnym w celu ustalenia terminu</li>";
        $htmlBody .= "<li>Nie używać aparatury do pomiarów do czasu przeprowadzenia kalibracji</li>";
        $htmlBody .= "<li>Zabezpieczyć aparaturę przed przypadkowym użyciem</li>";
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<p style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 4px;'>";
        $htmlBody .= "<strong>Uwaga:</strong> Używanie nieskalibrowanej aparatury pomiarowej może prowadzić do nieprawidłowych wyników pomiarów i naruszeń procedur jakości.";
        $htmlBody .= "</p>";
        
        $htmlBody .= "<p><strong>Zespół " . $this->settingService->get('app_name', 'AssetHub') . "</strong></p>";
        $htmlBody .= "</body></html>";

        $textBody = "UWAGA: PRZEKROCZENIE TERMINU KALIBRACJI!\n\n";
        $textBody .= "Witaj {$recipientName}!\n\n";
        $textBody .= "Informujemy, że następująca aparatura pomiarowa przekroczyła termin kalibracji:\n\n";
        
        foreach ($equipmentData['equipment'] as $equipment) {
            $textBody .= "• {$equipment['name']}\n";
            $textBody .= "  Nr inwentarzowy: {$equipment['inventory_number']}\n";
            $textBody .= "  Termin kalibracji: {$equipment['calibration_due']}\n";
            $textBody .= "  Dni po terminie: {$equipment['days_overdue']}\n";
            if (!empty($equipment['location'])) {
                $textBody .= "  Lokalizacja: {$equipment['location']}\n";
            }
            $textBody .= "\n";
        }
        
        $textBody .= "WYMAGANE DZIAŁANIA:\n";
        $textBody .= "• NATYCHMIAST wyłączyć aparaturę z użytkowania\n";
        $textBody .= "• Skontaktować się z laboratorium kalibracyjnym w celu ustalenia terminu\n";
        $textBody .= "• Nie używać aparatury do pomiarów do czasu przeprowadzenia kalibracji\n";
        $textBody .= "• Zabezpieczyć aparaturę przed przypadkowym użyciem\n\n";
        
        $textBody .= "UWAGA: Używanie nieskalibrowanej aparatury pomiarowej może prowadzić do nieprawidłowych wyników pomiarów i naruszeń procedur jakości.\n\n";
        
        $textBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->emailService->sendHtmlEmail(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $recipientName,
            'aparatura_overdue_calibration',
            $equipmentData
        );
    }

    /**
     * Wyślij przypomnienie o zbliżającym się terminie kalibracji
     */
    public function sendUpcomingCalibrationReminderEmail(string $recipientEmail, string $recipientName, array $equipmentData): bool
    {
        $subject = 'Przypomnienie: Zbliżający się termin kalibracji aparatury pomiarowej';
        
        $htmlBody = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $htmlBody .= "<h3 style='color: #ff9800;'>Przypomnienie o zbliżającym się terminie kalibracji</h3>";
        $htmlBody .= "<p>Witaj {$recipientName}!</p>";
        $htmlBody .= "<p>Informujemy, że dla następującej aparatury pomiarowej zbliża się termin kalibracji:</p>";
        
        $htmlBody .= "<div style='background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0;'>";
        $htmlBody .= "<h4 style='margin-top: 0; color: #856404;'>APARATURA WYMAGAJĄCA KALIBRACJI:</h4>";
        $htmlBody .= "<ul style='margin-bottom: 0;'>";
        
        foreach ($equipmentData['equipment'] as $equipment) {
            $htmlBody .= "<li><strong>{$equipment['name']}</strong><br>";
            $htmlBody .= "&nbsp;&nbsp;• Nr inwentarzowy: {$equipment['inventory_number']}<br>";
            $htmlBody .= "&nbsp;&nbsp;• Termin kalibracji: <strong style='color: #ff9800;'>{$equipment['calibration_due']}</strong><br>";
            $htmlBody .= "&nbsp;&nbsp;• Dni do terminu: <strong>{$equipment['days_until_due']}</strong><br>";
            if (!empty($equipment['location'])) {
                $htmlBody .= "&nbsp;&nbsp;• Lokalizacja: {$equipment['location']}<br>";
            }
            $htmlBody .= "</li>";
        }
        $htmlBody .= "</ul>";
        $htmlBody .= "</div>";
        
        $htmlBody .= "<h4>ZALECANE DZIAŁANIA:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li>Zaplanować kalibrację w laboratorium specjalistycznym</li>";
        $htmlBody .= "<li>Przygotować aparaturę do wysyłki na kalibrację</li>";
        $htmlBody .= "<li>Sprawdzić dostępność alternatywnej aparatury na czas kalibracji</li>";
        $htmlBody .= "<li>Skontaktować się z laboratorium w celu ustalenia terminu</li>";
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<p><strong>Pozdrawiamy,<br>";
        $htmlBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub') . "</strong></p>";
        $htmlBody .= "</body></html>";

        $textBody = "Przypomnienie o zbliżającym się terminie kalibracji\n\n";
        $textBody .= "Witaj {$recipientName}!\n\n";
        $textBody .= "Informujemy, że dla następującej aparatury pomiarowej zbliża się termin kalibracji:\n\n";
        
        foreach ($equipmentData['equipment'] as $equipment) {
            $textBody .= "• {$equipment['name']}\n";
            $textBody .= "  Nr inwentarzowy: {$equipment['inventory_number']}\n";
            $textBody .= "  Termin kalibracji: {$equipment['calibration_due']}\n";
            $textBody .= "  Dni do terminu: {$equipment['days_until_due']}\n";
            if (!empty($equipment['location'])) {
                $textBody .= "  Lokalizacja: {$equipment['location']}\n";
            }
            $textBody .= "\n";
        }
        
        $textBody .= "ZALECANE DZIAŁANIA:\n";
        $textBody .= "• Zaplanować kalibrację w laboratorium specjalistycznym\n";
        $textBody .= "• Przygotować aparaturę do wysyłki na kalibrację\n";
        $textBody .= "• Sprawdzić dostępność alternatywnej aparatury na czas kalibracji\n";
        $textBody .= "• Skontaktować się z laboratorium w celu ustalenia terminu\n\n";
        
        $textBody .= "Pozdrawiamy,\n";
        $textBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->emailService->sendHtmlEmail(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $recipientName,
            'aparatura_upcoming_calibration',
            $equipmentData
        );
    }

    /**
     * Wyślij powiadomienie o zakończeniu kalibracji
     */
    public function sendCalibrationCompletedEmail(string $recipientEmail, string $recipientName, array $reviewData): bool
    {
        $subject = 'Zakończenie kalibracji aparatury pomiarowej';
        
        $htmlBody = "<html><body style='font-family: Arial, sans-serif; line-height: 1.6;'>";
        $htmlBody .= "<h3 style='color: #4caf50;'>Zakończenie kalibracji aparatury pomiarowej</h3>";
        $htmlBody .= "<p>Witaj {$recipientName}!</p>";
        $htmlBody .= "<p>Informujemy, że została zakończona kalibracja przypisanej do Ciebie aparatury pomiarowej.</p>";
        
        $htmlBody .= "<div style='background-color: #e8f5e8; padding: 15px; border-left: 4px solid #4caf50; margin: 10px 0;'>";
        $htmlBody .= "<h4 style='margin-top: 0; color: #2e7d32;'>SZCZEGÓŁY ZAKOŃCZONEJ KALIBRACJI:</h4>";
        $htmlBody .= "<ul style='margin-bottom: 0;'>";
        $htmlBody .= "<li><strong>Numer kalibracji:</strong> {$reviewData['review_number']}</li>";
        $htmlBody .= "<li><strong>Data zakończenia:</strong> {$reviewData['completion_date']}</li>";
        $htmlBody .= "<li><strong>Laboratorium:</strong> {$reviewData['review_company']}</li>";
        $htmlBody .= "<li><strong>Wynik kalibracji:</strong> <span style='color: #4caf50; font-weight: bold;'>{$reviewData['result']}</span></li>";
        
        if (isset($reviewData['equipment_name'])) {
            $htmlBody .= "<li><strong>Aparatura:</strong> {$reviewData['equipment_name']}</li>";
        }
        
        if (isset($reviewData['set_name'])) {
            $htmlBody .= "<li><strong>Zestaw:</strong> {$reviewData['set_name']}</li>";
        }
        
        if (!empty($reviewData['next_calibration_date'])) {
            $htmlBody .= "<li><strong>Kolejna kalibracja:</strong> <span style='color: #ff9800; font-weight: bold;'>{$reviewData['next_calibration_date']}</span></li>";
        }
        $htmlBody .= "</ul>";
        $htmlBody .= "</div>";
        
        if (!empty($reviewData['notes'])) {
            $htmlBody .= "<h4>UWAGI Z KALIBRACJI:</h4>";
            $htmlBody .= "<p style='background-color: #f5f5f5; padding: 10px; border-radius: 4px;'>";
            $htmlBody .= nl2br(htmlspecialchars($reviewData['notes']));
            $htmlBody .= "</p>";
        }
        
        $htmlBody .= "<h4>DALSZE KROKI:</h4>";
        $htmlBody .= "<ul>";
        $htmlBody .= "<li>Aparatura została przywrócona do użytkowania</li>";
        $htmlBody .= "<li>Certyfikat kalibracji został dołączony do dokumentacji</li>";
        $htmlBody .= "<li>System automatycznie ustawi przypomnienie o kolejnej kalibracji</li>";
        $htmlBody .= "<li>Szczegóły kalibracji dostępne są w systemie AssetHub</li>";
        $htmlBody .= "</ul>";
        
        $htmlBody .= "<p><strong>Pozdrawiamy,<br>";
        $htmlBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub') . "</strong></p>";
        $htmlBody .= "</body></html>";

        $textBody = "Zakończenie kalibracji aparatury pomiarowej\n\n";
        $textBody .= "Witaj {$recipientName}!\n\n";
        $textBody .= "Informujemy, że została zakończona kalibracja przypisanej do Ciebie aparatury pomiarowej.\n\n";
        
        $textBody .= "SZCZEGÓŁY ZAKOŃCZONEJ KALIBRACJI:\n";
        $textBody .= "• Numer kalibracji: {$reviewData['review_number']}\n";
        $textBody .= "• Data zakończenia: {$reviewData['completion_date']}\n";
        $textBody .= "• Laboratorium: {$reviewData['review_company']}\n";
        $textBody .= "• Wynik kalibracji: {$reviewData['result']}\n";
        
        if (isset($reviewData['equipment_name'])) {
            $textBody .= "• Aparatura: {$reviewData['equipment_name']}\n";
        }
        
        if (isset($reviewData['set_name'])) {
            $textBody .= "• Zestaw: {$reviewData['set_name']}\n";
        }
        
        if (!empty($reviewData['next_calibration_date'])) {
            $textBody .= "• Kolejna kalibracja: {$reviewData['next_calibration_date']}\n";
        }
        $textBody .= "\n";
        
        if (!empty($reviewData['notes'])) {
            $textBody .= "UWAGI Z KALIBRACJI:\n";
            $textBody .= $reviewData['notes'] . "\n\n";
        }
        
        $textBody .= "DALSZE KROKI:\n";
        $textBody .= "• Aparatura została przywrócona do użytkowania\n";
        $textBody .= "• Certyfikat kalibracji został dołączony do dokumentacji\n";
        $textBody .= "• System automatycznie ustawi przypomnienie o kolejnej kalibracji\n";
        $textBody .= "• Szczegóły kalibracji dostępne są w systemie AssetHub\n\n";
        
        $textBody .= "Pozdrawiamy,\n";
        $textBody .= "Zespół " . $this->settingService->get('app_name', 'AssetHub');

        return $this->emailService->sendHtmlEmail(
            $recipientEmail,
            $subject,
            $htmlBody,
            $textBody,
            $recipientName,
            'aparatura_calibration_completed',
            $reviewData
        );
    }
}