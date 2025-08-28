<?php

namespace App\AparaturaPomiarowa\Service;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\Service\SettingService;
use Psr\Log\LoggerInterface;

class AparaturaPomiarowaPdfService
{
    public function __construct(
        private SettingService $settingService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Generuje PDF protokołu przekazania aparatury pomiarowej
     */
    public function generateTransferProtocolPDF(AparaturaPomiarowaTransfer $transfer): string
    {
        $equipmentSet = $transfer->getEquipmentSet();
        $equipment = $transfer->getEquipment();
        $recipient = $transfer->getRecipient();
        $handedBy = $transfer->getHandedBy();
        
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator('AssetHub System');
        $pdf->SetAuthor('AssetHub');
        $pdf->SetTitle('Protokół przekazania aparatury pomiarowej');
        $pdf->SetSubject('Protokół przekazania - ' . $transfer->getTransferNumber());
        
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();
        
        // Tytuł
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 15, 'PROTOKÓŁ PRZEKAZANIA APARATURY POMIAROWEJ', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Numer protokołu i data
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Numer protokołu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferNumber(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data przekazania:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferDate()->format('d.m.Y'), 0, 1, 'L');
        $pdf->Ln(5);
        
        if ($equipmentSet) {
            $this->addEquipmentSetSection($pdf, $equipmentSet, $transfer);
        } elseif ($equipment) {
            $this->addSingleEquipmentSection($pdf, $equipment);
        }
        
        // Informacje o odbiorcy
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ODBIORCA', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getEmail(), 0, 1, 'L');
        
        if ($recipient->getBranch()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Oddział:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $recipient->getBranch(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Informacje o przekazującym
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'PRZEKAZAŁ', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Cel przekazania
        if ($transfer->getPurpose()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Cel przekazania:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getPurpose(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Warunki przekazania
        if ($transfer->getConditions()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Warunki przekazania:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getConditions(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Uwagi
        if ($transfer->getNotes()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Uwagi:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getNotes(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Podpisy
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(90, 8, 'Podpis odbiorcy:', 0, 0, 'L');
        $pdf->Cell(90, 8, 'Podpis przekazującego:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 6, '_________________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_________________________________', 0, 1, 'C');
        $pdf->Cell(90, 6, $recipient->getFullName(), 0, 0, 'C');
        $pdf->Cell(90, 6, $handedBy->getFullName(), 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->Cell(0, 4, 'Data i miejsce:', 0, 1, 'L');
        $pdf->Cell(0, 4, '_________________________________', 0, 1, 'L');
        
        return $pdf->Output('', 'S');
    }

    /**
     * Generuje PDF protokołu zwrotu aparatury pomiarowej
     */
    public function generateReturnProtocolPDF(AparaturaPomiarowaTransfer $transfer): string
    {
        $equipmentSet = $transfer->getEquipmentSet();
        $equipment = $transfer->getEquipment();
        $recipient = $transfer->getRecipient();
        $handedBy = $transfer->getHandedBy();
        
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator('AssetHub System');
        $pdf->SetAuthor('AssetHub');
        $pdf->SetTitle('Protokół zwrotu aparatury pomiarowej');
        $pdf->SetSubject('Protokół zwrotu - ' . $transfer->getTransferNumber());
        
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();
        
        // Tytuł
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 15, 'PROTOKÓŁ ZWROTU APARATURY POMIAROWEJ', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Numer protokołu i daty
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Numer protokołu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferNumber() . ' - ZWROT', 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data zwrotu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, (new \DateTime())->format('d.m.Y'), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data przekazania:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferDate()->format('d.m.Y'), 0, 1, 'L');
        $pdf->Ln(5);
        
        if ($equipmentSet) {
            $this->addEquipmentSetSection($pdf, $equipmentSet, $transfer, true);
        } elseif ($equipment) {
            $this->addSingleEquipmentSection($pdf, $equipment, true);
        }
        
        // Informacje o zwracającym
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ZWRACAJĄCY', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Informacje o przyjmującym zwrot
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'PRZYJMUJĄCY ZWROT', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Uwagi dotyczące zwrotu
        if ($transfer->getReturnNotes()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Uwagi dotyczące zwrotu:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getReturnNotes(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Podpisy
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(90, 8, 'Podpis zwracającego:', 0, 0, 'L');
        $pdf->Cell(90, 8, 'Podpis przyjmującego:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 6, '_________________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_________________________________', 0, 1, 'C');
        $pdf->Cell(90, 6, $recipient->getFullName(), 0, 0, 'C');
        $pdf->Cell(90, 6, $handedBy->getFullName(), 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->Cell(0, 4, 'Data i miejsce:', 0, 1, 'L');
        $pdf->Cell(0, 4, '_________________________________', 0, 1, 'L');
        
        return $pdf->Output('', 'S');
    }

    /**
     * Generuje PDF protokołu kalibracji
     */
    public function generateCalibrationProtocolPDF(AparaturaPomiarowaReview $review): string
    {
        $equipment = $review->getEquipment();
        $equipmentSet = $review->getEquipmentSet();
        
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        $pdf->SetCreator('AssetHub System');
        $pdf->SetAuthor('AssetHub');
        $pdf->SetTitle('Protokół kalibracji aparatury pomiarowej');
        $pdf->SetSubject('Protokół kalibracji - ' . $review->getReviewNumber());
        
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(TRUE, 25);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();
        
        // Tytuł
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 15, 'PROTOKÓŁ KALIBRACJI APARATURY POMIAROWEJ', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Numer protokołu i data
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Numer kalibracji:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $review->getReviewNumber(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data kalibracji:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $review->getReviewDate()->format('d.m.Y'), 0, 1, 'L');
        
        if ($review->getCompletedAt()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(60, 8, 'Data zakończenia:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $review->getCompletedAt()->format('d.m.Y'), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Typ kalibracji
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Typ kalibracji:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $review->getReviewType() ?? 'Standardowa', 0, 1, 'L');
        
        // Status
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Status:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $review->getStatusDisplayName(), 0, 1, 'L');
        $pdf->Ln(5);
        
        if ($equipmentSet) {
            $this->addEquipmentSetCalibrationSection($pdf, $equipmentSet, $review);
        } elseif ($equipment) {
            $this->addSingleEquipmentCalibrationSection($pdf, $equipment);
        }
        
        // Laboratorium kalibracyjne
        if ($review->getReviewCompany()) {
            $pdf->SetFont('dejavusans', 'B', 14);
            $pdf->Cell(0, 10, 'LABORATORIUM KALIBRACYJNE', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $review->getReviewCompany(), 0, 1, 'L');
            $pdf->Ln(5);
        }
        
        // Wyniki kalibracji
        if ($review->getResult()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Wynik kalibracji:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $review->getResult(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Uwagi
        if ($review->getNotes()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Uwagi:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $review->getNotes(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Kolejna kalibracja
        if ($review->getNextReviewDate()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(60, 8, 'Kolejna kalibracja:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 12);
            $pdf->Cell(0, 8, $review->getNextReviewDate()->format('d.m.Y'), 0, 1, 'L');
        }
        
        // Podpis
        $pdf->Ln(15);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, 'Podpis osoby odpowiedzialnej:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, '_________________________________', 0, 1, 'L');
        
        $pdf->Ln(5);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->Cell(0, 4, 'Data i miejsce:', 0, 1, 'L');
        $pdf->Cell(0, 4, '_________________________________', 0, 1, 'L');
        
        return $pdf->Output('', 'S');
    }

    /**
     * Dodaje sekcję z informacjami o zestawie aparatury
     */
    private function addEquipmentSetSection(\TCPDF $pdf, AparaturaPomiarowaEquipmentSet $equipmentSet, ?AparaturaPomiarowaTransfer $transfer = null, bool $isReturn = false): void
    {
        // Informacje o zestawie
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'DANE ZESTAWU APARATURY', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipmentSet->getName(), 0, 1, 'L');
        
        if ($equipmentSet->getSetType()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Typ:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getSetType(), 0, 1, 'L');
        }
        
        if ($equipmentSet->getLocation()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Lokalizacja:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getLocation(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Lista aparatury w zestawie
        $title = $isReturn ? 'APARATURA DO ZWROTU' : 'ELEMENTY ZESTAWU';
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(10, 8, 'Lp.', 1, 0, 'C');
        $pdf->Cell(60, 8, 'Nazwa', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Numer inwentarzowy', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Typ', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Producent', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Status', 1, 1, 'C');
        
        $pdf->SetFont('dejavusans', '', 8);
        $counter = 1;
        $equipmentToShow = $equipmentSet->getEquipment();
        
        // Jeśli to transfer z wybranymi elementami, pokaż tylko te wybrane
        if ($transfer && $transfer->hasSelectedEquipment()) {
            $selectedIds = $transfer->getSelectedEquipmentIds();
            $equipmentToShow = $equipmentSet->getEquipment()->filter(
                fn($equipment) => in_array($equipment->getId(), $selectedIds)
            );
        }
        
        foreach ($equipmentToShow as $equipment) {
            $pdf->Cell(10, 6, $counter++, 1, 0, 'C');
            $pdf->Cell(60, 6, $equipment->getName(), 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getInventoryNumber(), 1, 0, 'C');
            $pdf->Cell(25, 6, $equipment->getEquipmentType() ?? '', 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getManufacturer() ?? '', 1, 0, 'L');
            $pdf->Cell(20, 6, $equipment->getStatusDisplayName(), 1, 1, 'C');
        }
        $pdf->Ln(5);
    }

    /**
     * Dodaje sekcję z informacjami o pojedynczym urządzeniu
     */
    private function addSingleEquipmentSection(\TCPDF $pdf, AparaturaPomiarowaEquipment $equipment, bool $isReturn = false): void
    {
        $title = $isReturn ? 'URZĄDZENIE DO ZWROTU' : 'DANE URZĄDZENIA';
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $title, 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipment->getName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Numer inwentarzowy:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipment->getInventoryNumber(), 0, 1, 'L');
        
        if ($equipment->getEquipmentType()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Typ:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getEquipmentType(), 0, 1, 'L');
        }
        
        if ($equipment->getManufacturer()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Producent:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getManufacturer(), 0, 1, 'L');
        }
        
        if ($equipment->getModel()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Model:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getModel(), 0, 1, 'L');
        }
        
        if ($equipment->getSerialNumber()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Numer seryjny:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getSerialNumber(), 0, 1, 'L');
        }
        
        if ($equipment->getLocation()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Lokalizacja:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getLocation(), 0, 1, 'L');
        }
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Status:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipment->getStatusDisplayName(), 0, 1, 'L');
        $pdf->Ln(5);
    }

    /**
     * Dodaje sekcję z informacjami o zestawie do kalibracji
     */
    private function addEquipmentSetCalibrationSection(\TCPDF $pdf, AparaturaPomiarowaEquipmentSet $equipmentSet, AparaturaPomiarowaReview $review): void
    {
        // Informacje o zestawie
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ZESTAW APARATURY DO KALIBRACJI', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipmentSet->getName(), 0, 1, 'L');
        
        if ($equipmentSet->getSetType()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Typ:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getSetType(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Lista aparatury do kalibracji
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'APARATURA OBJĘTA KALIBRACJĄ', 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(10, 8, 'Lp.', 1, 0, 'C');
        $pdf->Cell(50, 8, 'Nazwa', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Nr inwentarzowy', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Nr seryjny', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Producent', 1, 0, 'C');
        $pdf->Cell(30, 8, 'Następna kalibracja', 1, 1, 'C');
        
        $pdf->SetFont('dejavusans', '', 8);
        $counter = 1;
        $equipmentToShow = $equipmentSet->getEquipment();
        
        // Jeśli kalibracja ma wybrane elementy, pokaż tylko te wybrane
        if ($review->hasSelectedEquipment()) {
            $selectedIds = $review->getSelectedEquipmentIds();
            $equipmentToShow = $equipmentSet->getEquipment()->filter(
                fn($equipment) => in_array($equipment->getId(), $selectedIds)
            );
        }
        
        foreach ($equipmentToShow as $equipment) {
            $pdf->Cell(10, 6, $counter++, 1, 0, 'C');
            $pdf->Cell(50, 6, $equipment->getName(), 1, 0, 'L');
            $pdf->Cell(30, 6, $equipment->getInventoryNumber(), 1, 0, 'C');
            $pdf->Cell(30, 6, $equipment->getSerialNumber() ?? '', 1, 0, 'C');
            $pdf->Cell(25, 6, $equipment->getManufacturer() ?? '', 1, 0, 'L');
            $nextCalibration = $equipment->getNextReviewDate() ? $equipment->getNextReviewDate()->format('d.m.Y') : '';
            $pdf->Cell(30, 6, $nextCalibration, 1, 1, 'C');
        }
        $pdf->Ln(5);
    }

    /**
     * Dodaje sekcję z informacjami o pojedynczym urządzeniu do kalibracji
     */
    private function addSingleEquipmentCalibrationSection(\TCPDF $pdf, AparaturaPomiarowaEquipment $equipment): void
    {
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'URZĄDZENIE DO KALIBRACJI', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipment->getName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Numer inwentarzowy:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipment->getInventoryNumber(), 0, 1, 'L');
        
        if ($equipment->getSerialNumber()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Numer seryjny:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getSerialNumber(), 0, 1, 'L');
        }
        
        if ($equipment->getManufacturer()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Producent:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getManufacturer(), 0, 1, 'L');
        }
        
        if ($equipment->getModel()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Model:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getModel(), 0, 1, 'L');
        }
        
        if ($equipment->getNextReviewDate()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Następna kalibracja:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipment->getNextReviewDate()->format('d.m.Y'), 0, 1, 'L');
        }
        $pdf->Ln(5);
    }
}