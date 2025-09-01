<?php

namespace App\DataFixtures;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReviewEquipment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class AparaturaPomiarowaFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Get users for assignments
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        if (!$admin) {
            throw new \Exception('Admin user not found. Please load AppFixtures first.');
        }

        // Check if AparaturaPomiarowa module exists
        $moduleRepository = $manager->getRepository(\App\Entity\Module::class);
        $aparaturaModule = $moduleRepository->findOneBy(['name' => 'aparatura_pomiarowa']);
        
        if (!$aparaturaModule) {
            echo "Module 'aparatura_pomiarowa' does not exist. Run AppFixtures first to create it.\n";
            return;
        }

        // Sample equipment data
        $equipmentData = [
            [
                'inventoryNumber' => 'APM-001',
                'name' => 'Multimetr cyfrowy Fluke 287',
                'type' => 'multimeter',
                'manufacturer' => 'Fluke',
                'model' => '287',
                'serialNumber' => 'FL287-001-2024',
                'price' => 2450.00,
                'supplier' => 'ELMARK',
                'description' => 'Precyzyjny multimetr cyfrowy z rejestracją danych',
                'location' => 'Laboratorium A1',
                'projekt' => 'PROJEKT-001',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-002',
                'name' => 'Oscyloskop Tektronix TDS2024C',
                'type' => 'oscilloscope',
                'manufacturer' => 'Tektronix',
                'model' => 'TDS2024C',
                'serialNumber' => 'TEK2024-002-2024',
                'price' => 8900.00,
                'supplier' => 'ZOPAN',
                'description' => 'Oscyloskop cyfrowy 4-kanałowy 200 MHz',
                'location' => 'Laboratorium A2',
                'projekt' => 'PROJEKT-002',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-003',
                'name' => 'Generator funkcyjny Agilent 33220A',
                'type' => 'generator',
                'manufacturer' => 'Agilent',
                'model' => '33220A',
                'serialNumber' => 'AG33220-003-2024',
                'price' => 4200.00,
                'supplier' => 'TECHNO-PAL',
                'description' => 'Generator funkcyjny 20 MHz z modulacją AM/FM',
                'location' => 'Laboratorium A1',
                'projekt' => 'PROJEKT-001',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-004',
                'name' => 'Miernik LCR Keysight E4980AL',
                'type' => 'lcr_meter',
                'manufacturer' => 'Keysight',
                'model' => 'E4980AL',
                'serialNumber' => 'KEY4980-004-2024',
                'price' => 12500.00,
                'supplier' => 'ELMARK',
                'description' => 'Precyzyjny miernik LCR do 2 MHz',
                'location' => 'Laboratorium B1',
                'projekt' => 'PROJEKT-003',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-005',
                'name' => 'Zasilacz programowalny Rigol DP832',
                'type' => 'power_supply',
                'manufacturer' => 'Rigol',
                'model' => 'DP832',
                'serialNumber' => 'RIG832-005-2024',
                'price' => 1850.00,
                'supplier' => 'TME',
                'description' => 'Zasilacz programowalny 3-kanałowy 30V/3A',
                'location' => 'Laboratorium A2',
                'projekt' => 'PROJEKT-002',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-006',
                'name' => 'Analizator widma Rohde & Schwarz FSW',
                'type' => 'spectrum_analyzer',
                'manufacturer' => 'Rohde & Schwarz',
                'model' => 'FSW-B8',
                'serialNumber' => 'RS-FSW-006-2024',
                'price' => 89000.00,
                'supplier' => 'RADMOR',
                'description' => 'Analizator widma sygnałów do 8 GHz',
                'location' => 'Laboratorium C1',
                'projekt' => 'PROJEKT-004',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-007',
                'name' => 'Watomierz Yokogawa WT3000',
                'type' => 'power_meter',
                'manufacturer' => 'Yokogawa',
                'model' => 'WT3000',
                'serialNumber' => 'YOK3000-007-2024',
                'price' => 15600.00,
                'supplier' => 'YOKOGAWA POLSKA',
                'description' => 'Precyzyjny watomierz cyfrowy klasa 0.02',
                'location' => 'Laboratorium B2',
                'projekt' => 'PROJEKT-003',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-008',
                'name' => 'Kalibrator uniwersalny Fluke 5520A',
                'type' => 'calibrator',
                'manufacturer' => 'Fluke',
                'model' => '5520A',
                'serialNumber' => 'FL5520-008-2024',
                'price' => 32000.00,
                'supplier' => 'ELMARK',
                'description' => 'Kalibrator wielofunkcyjny wysokiej dokładności',
                'location' => 'Laboratorium Wzorców',
                'projekt' => 'WZORCE-001',
                'reviewMonths' => 6
            ],
            [
                'inventoryNumber' => 'APM-009',
                'name' => 'Termometr cyfrowy Pt100 Dostmann P755',
                'type' => 'thermometer',
                'manufacturer' => 'Dostmann',
                'model' => 'P755',
                'serialNumber' => 'DOS755-009-2024',
                'price' => 890.00,
                'supplier' => 'KOBRABID',
                'description' => 'Termometr przemysłowy z sondą Pt100',
                'location' => 'Laboratorium A1',
                'projekt' => 'PROJEKT-001',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APM-010',
                'name' => 'Mikroskop pomiarowy Mitutoyo FS70',
                'type' => 'microscope',
                'manufacturer' => 'Mitutoyo',
                'model' => 'FS70',
                'serialNumber' => 'MIT-FS70-010-2024',
                'price' => 24500.00,
                'supplier' => 'MITUTOYO POLSKA',
                'description' => 'Mikroskop pomiarowy do kontroli wymiarowej',
                'location' => 'Laboratorium Metrologii',
                'projekt' => 'METROLOGIA-001',
                'reviewMonths' => 12
            ]
        ];

        // Create equipment
        $equipment = [];
        foreach ($equipmentData as $data) {
            $eq = new AparaturaPomiarowaEquipment();
            $eq->setInventoryNumber($data['inventoryNumber'])
                ->setName($data['name'])
                ->setEquipmentType($data['type'])
                ->setManufacturer($data['manufacturer'])
                ->setModel($data['model'])
                ->setSerialNumber($data['serialNumber'])
                ->setPurchasePrice($data['price'])
                ->setSupplier($data['supplier'])
                ->setDescription($data['description'])
                ->setLocation($data['location'])
                ->setProjekt($data['projekt'])
                ->setReviewIntervalMonths($data['reviewMonths'])
                ->setStatus(AparaturaPomiarowaEquipment::STATUS_AVAILABLE)
                ->setCreatedBy($admin);

            $manager->persist($eq);
            $equipment[] = $eq;
        }

        // Create equipment sets
        $equipmentSetData = [
            [
                'name' => 'Zestaw podstawowy pomiarów elektrycznych',
                'description' => 'Podstawowy zestaw do pomiarów elektrycznych w laboratorium',
                'type' => 'electrical_basic',
                'location' => 'Laboratorium A1',
                'equipment_indices' => [0, 2, 4] // Multimetr, Generator, Zasilacz
            ],
            [
                'name' => 'Zestaw zaawansowany analizy sygnałów',
                'description' => 'Zaawansowany zestaw do analizy i generacji sygnałów',
                'type' => 'signal_analysis',
                'location' => 'Laboratorium A2',
                'equipment_indices' => [1, 5] // Oscyloskop, Analizator widma
            ],
            [
                'name' => 'Zestaw precyzyjnych pomiarów',
                'description' => 'Zestaw do precyzyjnych pomiarów i kalibracji',
                'type' => 'precision_measurement',
                'location' => 'Laboratorium B1',
                'equipment_indices' => [3, 6, 7] // LCR, Watomierz, Kalibrator
            ],
            [
                'name' => 'Zestaw kontroli jakości',
                'description' => 'Kompletny zestaw do kontroli jakości i metrologii',
                'type' => 'quality_control',
                'location' => 'Laboratorium Metrologii',
                'equipment_indices' => [8, 9] // Termometr, Mikroskop
            ]
        ];

        $equipmentSets = [];
        foreach ($equipmentSetData as $data) {
            $set = new AparaturaPomiarowaEquipmentSet();
            $set->setName($data['name'])
                ->setDescription($data['description'])
                ->setSetType($data['type'])
                ->setLocation($data['location'])
                ->setStatus(AparaturaPomiarowaEquipmentSet::STATUS_AVAILABLE)
                ->setCreatedBy($admin);

            foreach ($data['equipment_indices'] as $index) {
                if (isset($equipment[$index])) {
                    $set->addEquipment($equipment[$index]);
                }
            }

            $manager->persist($set);
            $equipmentSets[] = $set;
        }

        // Create reviews/calibrations
        $reviewData = [
            [
                'equipment_set_index' => 0,
                'review_type' => AparaturaPomiarowaReview::TYPE_PERIODIC,
                'planned_date' => '+30 days',
                'company' => 'CALIBRA Sp. z o.o.',
                'notes' => 'Kalibracja podstawowych przyrządów pomiarowych'
            ],
            [
                'equipment_index' => 7, // Kalibrator
                'review_type' => AparaturaPomiarowaReview::TYPE_PERIODIC,
                'planned_date' => '+14 days',
                'company' => 'WZORCOWNIA LAB',
                'notes' => 'Kalibracja etalonu roboczego - wysoka priorytet'
            ],
            [
                'equipment_set_index' => 2,
                'review_type' => AparaturaPomiarowaReview::TYPE_INITIAL,
                'planned_date' => '+60 days',
                'company' => 'PRECYZJA TECH',
                'notes' => 'Weryfikacja zestawu precyzyjnych pomiarów'
            ]
        ];

        $reviews = [];
        foreach ($reviewData as $data) {
            $review = new AparaturaPomiarowaReview();
            
            if (isset($data['equipment_set_index'])) {
                $review->setEquipmentSet($equipmentSets[$data['equipment_set_index']]);
            } elseif (isset($data['equipment_index'])) {
                $review->setEquipment($equipment[$data['equipment_index']]);
            }

            $plannedDate = new \DateTime($data['planned_date']);
            $review->setReviewType($data['review_type'])
                ->setPlannedDate($plannedDate)
                ->setReviewCompany($data['company'])
                ->setNotes($data['notes'])
                ->setStatus(AparaturaPomiarowaReview::STATUS_PREPARATION)
                ->setCreatedBy($admin);

            $manager->persist($review);
            $reviews[] = $review;
        }

        // Create transfers
        $transferData = [
            [
                'equipment_set_index' => 1,
                'recipient_username' => 'admin',
                'transfer_date' => '+7 days',
                'purpose' => 'Testy zgodności EMC',
                'location' => 'Laboratorium EMC',
                'notes' => 'Transfer na czas przeprowadzenia testów'
            ],
            [
                'equipment_index' => 0, // Multimetr
                'recipient_username' => 'admin',
                'transfer_date' => '+3 days',
                'purpose' => 'Pomiary terenowe',
                'location' => 'Obiekt zewnętrzny',
                'notes' => 'Krótkoterminowy transfer na pomiary'
            ]
        ];

        foreach ($transferData as $data) {
            $recipient = $manager->getRepository(User::class)->findOneBy(['username' => $data['recipient_username']]);
            
            if ($recipient) {
                $transfer = new AparaturaPomiarowaTransfer();
                
                if (isset($data['equipment_set_index'])) {
                    $transfer->setEquipmentSet($equipmentSets[$data['equipment_set_index']]);
                } elseif (isset($data['equipment_index'])) {
                    $transfer->setEquipment($equipment[$data['equipment_index']]);
                }

                $transferDate = new \DateTime($data['transfer_date']);
                $transfer->setRecipient($recipient)
                    ->setTransferDate($transferDate)
                    ->setPurpose($data['purpose'])
                    ->setLocation($data['location'])
                    ->setNotes($data['notes'])
                    ->setHandedBy($admin)
                    ->setCreatedBy($admin);

                $manager->persist($transfer);
            }
        }

        $manager->flush();

        echo "Utworzono przykładowe dane dla modułu Aparatura Pomiarowa:\n";
        echo "- " . count($equipment) . " urządzeń pomiarowych\n";
        echo "- " . count($equipmentSets) . " zestawów sprzętu\n";
        echo "- " . count($reviewData) . " przeglądów/kalibracji\n";
        echo "- " . count($transferData) . " transferów\n";
    }

    public static function getGroups(): array
    {
        return ['aparatura-pomiarowa'];
    }
}