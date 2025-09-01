<?php

namespace App\DataFixtures;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReviewEquipment;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AparaturaPomiarowaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Get users for assignments
        $admin = $manager->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $user1 = $manager->getRepository(User::class)->findOneBy(['username' => 'user']);
        
        if (!$admin) {
            // Create admin user if not exists
            $admin = new User();
            $admin->setUsername('admin');
            $admin->setEmail('admin@example.com');
            $admin->setFirstName('Admin');
            $admin->setLastName('System');
            $admin->setPassword('$2y$13$password_hash_here'); // This should be properly hashed
            $manager->persist($admin);
            $manager->flush();
        }

        if (!$user1) {
            // Create user if not exists
            $user1 = new User();
            $user1->setUsername('user');
            $user1->setEmail('user@example.com');
            $user1->setFirstName('Jan');
            $user1->setLastName('Kowalski');
            $user1->setPassword('$2y$13$password_hash_here'); // This should be properly hashed
            $manager->persist($user1);
            $manager->flush();
        }

        // Create measurement equipment
        $equipmentData = [
            [
                'inventoryNumber' => 'APAR-001',
                'name' => 'Multimetr Fluke 87V',
                'type' => 'multimeter',
                'manufacturer' => 'Fluke Corporation',
                'model' => '87V',
                'serialNumber' => 'FL87V123456',
                'price' => '1250.00',
                'supplier' => 'TME Sp. z o.o.',
                'description' => 'Multimetr przemysłowy True RMS z funkcjami pomiarowymi AC/DC',
                'location' => 'Laboratorium A-101',
                'projekt' => 'LAB-2024-001',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APAR-002',
                'name' => 'Oscyloskop Rigol DS1054Z',
                'type' => 'oscilloscope',
                'manufacturer' => 'Rigol Technologies',
                'model' => 'DS1054Z',
                'serialNumber' => 'RG1054Z789012',
                'price' => '2800.00',
                'supplier' => 'Distrelec Sp. z o.o.',
                'description' => 'Oscyloskop cyfrowy 4-kanałowy 50MHz z dekodowaniem',
                'location' => 'Laboratorium A-102',
                'projekt' => 'LAB-2024-002',
                'reviewMonths' => 24
            ],
            [
                'inventoryNumber' => 'APAR-003',
                'name' => 'Generator sygnałów Keysight 33500B',
                'type' => 'generator',
                'manufacturer' => 'Keysight Technologies',
                'model' => '33500B',
                'serialNumber' => 'KS33500B345678',
                'price' => '4500.00',
                'supplier' => 'Keysight Polska',
                'description' => 'Generator funkcyjny 30MHz z arbitralnym kształtem fali',
                'location' => 'Laboratorium A-103',
                'projekt' => 'LAB-2024-003',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APAR-004',
                'name' => 'Miernik LCR Keysight E4980A',
                'type' => 'lcr_meter',
                'manufacturer' => 'Keysight Technologies',
                'model' => 'E4980A',
                'serialNumber' => 'KSE4980A456789',
                'price' => '15000.00',
                'supplier' => 'Keysight Polska',
                'description' => 'Precyzyjny miernik LCR 20Hz-2MHz z funkcją DC bias',
                'location' => 'Laboratorium A-104',
                'projekt' => 'LAB-2024-004',
                'reviewMonths' => 6
            ],
            [
                'inventoryNumber' => 'APAR-005',
                'name' => 'Zasilacz programowalny Keithley 2230-30-1',
                'type' => 'power_supply',
                'manufacturer' => 'Keithley Instruments',
                'model' => '2230-30-1',
                'serialNumber' => 'KI2230567890',
                'price' => '3200.00',
                'supplier' => 'TME Sp. z o.o.',
                'description' => 'Zasilacz 3-kanałowy, 2x30V/1A + 5V/1A, programowalny',
                'location' => 'Laboratorium B-201',
                'projekt' => 'LAB-2024-005',
                'reviewMonths' => 18
            ],
            [
                'inventoryNumber' => 'APAR-006',
                'name' => 'Analizator widma Rohde & Schwarz FSW',
                'type' => 'spectrum_analyzer',
                'manufacturer' => 'Rohde & Schwarz',
                'model' => 'FSW26',
                'serialNumber' => 'RSFSW678901',
                'price' => '85000.00',
                'supplier' => 'Rohde & Schwarz Polska',
                'description' => 'Analizator widma wysokiej klasy 2Hz-26.5GHz',
                'location' => 'Laboratorium B-202',
                'projekt' => 'LAB-2024-006',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APAR-007',
                'name' => 'Miernik mocy Rhode & Schwarz NRP-Z21',
                'type' => 'power_meter',
                'manufacturer' => 'Rohde & Schwarz',
                'model' => 'NRP-Z21',
                'serialNumber' => 'RSNRPZ789012',
                'price' => '4800.00',
                'supplier' => 'Rohde & Schwarz Polska',
                'description' => 'Głowica pomiarowa mocy RF 10MHz-18GHz',
                'location' => 'Laboratorium B-203',
                'projekt' => 'LAB-2024-007',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APAR-008',
                'name' => 'Kalibrator Fluke 5522A',
                'type' => 'calibrator',
                'manufacturer' => 'Fluke Corporation',
                'model' => '5522A',
                'serialNumber' => 'FL5522A890123',
                'price' => '25000.00',
                'supplier' => 'Fluke Polska',
                'description' => 'Wielofunkcyjny kalibrator elektryczny AC/DC',
                'location' => 'Laboratorium Wzorców',
                'projekt' => 'LAB-2024-008',
                'reviewMonths' => 6
            ],
            [
                'inventoryNumber' => 'APAR-009',
                'name' => 'Analizator impedancji Keysight E4990A',
                'type' => 'impedance_analyzer',
                'manufacturer' => 'Keysight Technologies',
                'model' => 'E4990A',
                'serialNumber' => 'KSE4990A901234',
                'price' => '45000.00',
                'supplier' => 'Keysight Polska',
                'description' => 'Analizator impedancji 20Hz-10MHz/20MHz/30MHz/50MHz/120MHz',
                'location' => 'Laboratorium C-301',
                'projekt' => 'LAB-2024-009',
                'reviewMonths' => 12
            ],
            [
                'inventoryNumber' => 'APAR-010',
                'name' => 'Częstościomierz Keysight 53230A',
                'type' => 'frequency_counter',
                'manufacturer' => 'Keysight Technologies',
                'model' => '53230A',
                'serialNumber' => 'KS53230A012345',
                'price' => '8500.00',
                'supplier' => 'Keysight Polska',
                'description' => 'Uniwersalny licznik częstotliwości 350MHz, 12 cyfr/s',
                'location' => 'Laboratorium C-302',
                'projekt' => 'LAB-2024-010',
                'reviewMonths' => 24
            ]
        ];

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
                ->setCreatedBy($admin)
                ->setManufacturingDate(new \DateTime('-' . rand(12, 48) . ' months'))
                ->setPurchaseDate(new \DateTime('-' . rand(6, 24) . ' months'))
                ->setWarrantyExpiry(new \DateTime('+' . rand(6, 36) . ' months'))
                ->setNextReviewDate(new \DateTime('+' . rand(30, 365) . ' days'));
            
            // Assign some equipment to users
            if (rand(1, 3) === 1) {
                $eq->setAssignedTo(rand(1, 2) === 1 ? $admin : $user1)
                   ->setStatus(AparaturaPomiarowaEquipment::STATUS_ASSIGNED)
                   ->setAssignedDate(new \DateTime('-' . rand(1, 30) . ' days'));
            }

            $equipment[] = $eq;
            $manager->persist($eq);
        }

        // Create equipment sets
        $setData = [
            [
                'name' => 'Zestaw Pomiarowy Podstawowy',
                'type' => 'basic',
                'description' => 'Podstawowy zestaw do pomiarów elektrycznych w laboratorium',
                'equipment_indices' => [0, 1, 4] // Multimetr, Oscyloskop, Zasilacz
            ],
            [
                'name' => 'Zestaw Elektroniczny Zaawansowany',
                'type' => 'electronic',
                'description' => 'Zaawansowany zestaw do pomiarów elektronicznych i RF',
                'equipment_indices' => [2, 5, 6] // Generator, Analizator widma, Miernik mocy
            ],
            [
                'name' => 'Zestaw Specjalistyczny LCR',
                'type' => 'specialist',
                'description' => 'Specjalistyczny zestaw do pomiarów parametrów RLC',
                'equipment_indices' => [3, 8] // Miernik LCR, Analizator impedancji
            ],
            [
                'name' => 'Zestaw Laboratoryjny Wzorcowy',
                'type' => 'laboratory',
                'description' => 'Zestaw urządzeń wzorcowych do kalibracji',
                'equipment_indices' => [7, 9] // Kalibrator, Częstościomierz
            ]
        ];

        $equipmentSets = [];
        foreach ($setData as $setInfo) {
            $set = new AparaturaPomiarowaEquipmentSet();
            $set->setName($setInfo['name'])
                ->setSetType($setInfo['type'])
                ->setDescription($setInfo['description'])
                ->setStatus(AparaturaPomiarowaEquipmentSet::STATUS_AVAILABLE)
                ->setCreatedBy($admin)
                ->setReviewIntervalMonths(12)
                ->setNextReviewDate(new \DateTime('+' . rand(60, 300) . ' days'))
                ->setLocation('Magazyn Sprzętu Pomiarowego');

            // Add equipment to set
            foreach ($setInfo['equipment_indices'] as $index) {
                if (isset($equipment[$index])) {
                    $set->addEquipment($equipment[$index]);
                }
            }

            // Assign some sets
            if (rand(1, 3) === 1) {
                $set->setAssignedTo(rand(1, 2) === 1 ? $admin : $user1)
                   ->setStatus(AparaturaPomiarowaEquipmentSet::STATUS_ASSIGNED)
                   ->setAssignedDate(new \DateTime('-' . rand(1, 15) . ' days'));
            }

            $equipmentSets[] = $set;
            $manager->persist($set);
        }

        $manager->flush();

        // Create sample reviews
        $reviewData = [
            [
                'equipment' => $equipment[0], // Multimetr
                'type' => 'periodic',
                'status' => 'completed',
                'laboratory' => 'Laboratorium Kalibracji TME',
                'result' => 'passed'
            ],
            [
                'equipment' => $equipment[3], // Miernik LCR
                'type' => 'initial',
                'status' => 'sent',
                'laboratory' => 'Keysight Calibration Lab',
                'result' => null
            ],
            [
                'equipmentSet' => $equipmentSets[0], // Zestaw podstawowy
                'type' => 'periodic',
                'status' => 'preparation',
                'laboratory' => 'Centrum Metrologii',
                'result' => null
            ]
        ];

        foreach ($reviewData as $reviewInfo) {
            $review = new AparaturaPomiarowaReview();
            $review->setCalibrationType($reviewInfo['type'])
                   ->setStatus($reviewInfo['status'])
                   ->setReviewCompany($reviewInfo['laboratory'])
                   ->setCreatedBy($admin)
                   ->setPreparedBy($admin)
                   ->setPlannedDate(new \DateTime('+' . rand(7, 30) . ' days'))
                   ->setCost(rand(200, 2000) . '.00')
                   ->setNotes('Kalibracja utworzona automatycznie przez system fixtures');

            if (isset($reviewInfo['equipment'])) {
                $review->setEquipment($reviewInfo['equipment']);
                
                // Create ReviewEquipment entry
                $reviewEquipment = new AparaturaPomiarowaReviewEquipment();
                $reviewEquipment->setReview($review)
                               ->setEquipment($reviewInfo['equipment'])
                               ->setWasInSetAtReview(false)
                               ->setCreatedAt(new \DateTime());
                $manager->persist($reviewEquipment);
            }

            if (isset($reviewInfo['equipmentSet'])) {
                $review->setEquipmentSet($reviewInfo['equipmentSet']);
                
                // Create ReviewEquipment entries for all equipment in set
                foreach ($reviewInfo['equipmentSet']->getEquipment() as $eq) {
                    $reviewEquipment = new AparaturaPomiarowaReviewEquipment();
                    $reviewEquipment->setReview($review)
                                   ->setEquipment($eq)
                                   ->setWasInSetAtReview(true)
                                   ->setCreatedAt(new \DateTime());
                    $manager->persist($reviewEquipment);
                }
            }

            if ($reviewInfo['status'] === 'sent') {
                $review->setSentDate(new \DateTime('-' . rand(1, 10) . ' days'))
                       ->setSentBy($admin);
            } elseif ($reviewInfo['status'] === 'completed') {
                $review->setSentDate(new \DateTime('-' . rand(10, 30) . ' days'))
                       ->setSentBy($admin)
                       ->setCompletedDate(new \DateTime('-' . rand(1, 10) . ' days'))
                       ->setCompletedBy($admin)
                       ->setResult($reviewInfo['result'])
                       ->setCertificateNumber('CERT-' . date('Y') . '-' . rand(1000, 9999))
                       ->setNextReviewDate(new \DateTime('+' . rand(300, 400) . ' days'))
                       ->setFindings('Wszystkie parametry w normie')
                       ->setRecommendations('Kontynuować użytkowanie zgodnie z instrukcją');
            }

            $manager->persist($review);
        }

        // Create sample transfers
        $transferData = [
            [
                'equipmentSet' => $equipmentSets[1], // Zestaw elektroniczny
                'type' => 'handover',
                'recipient' => $user1,
                'status' => 'completed'
            ],
            [
                'equipment' => $equipment[5], // Analizator widma
                'type' => 'return',
                'recipient' => $admin,
                'status' => 'in_progress'
            ]
        ];

        foreach ($transferData as $transferInfo) {
            $transfer = new AparaturaPomiarowaTransfer();
            $transfer->setRecipient($transferInfo['recipient'])
                     ->setStatus($transferInfo['status'])
                     ->setCreatedBy($admin)
                     ->setHandedBy($admin)
                     ->setTransferDate(new \DateTime('+' . rand(1, 7) . ' days'))
                     ->setNotes('Transfer utworzony przez system fixtures - ' . $transferInfo['type']);

            if (isset($transferInfo['equipment'])) {
                $transfer->setEquipment($transferInfo['equipment']);
            }

            if (isset($transferInfo['equipmentSet'])) {
                $transfer->setEquipmentSet($transferInfo['equipmentSet']);
            }

            if ($transferInfo['status'] === 'completed') {
                $transfer->setReturnDate(new \DateTime('-' . rand(1, 5) . ' days'))
                         ->setHandedBy($admin);
            }

            $manager->persist($transfer);
        }

        $manager->flush();

        echo "Utworzono przykładowe dane dla modułu Aparatura Pomiarowa:\n";
        echo "- " . count($equipment) . " urządzeń pomiarowych\n";
        echo "- " . count($equipmentSets) . " zestawów sprzętu\n";
        echo "- " . count($reviewData) . " przeglądów/kalibracji\n";
        echo "- " . count($transferData) . " transferów\n";
    }

}