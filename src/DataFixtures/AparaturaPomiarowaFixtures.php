<?php

namespace App\DataFixtures;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
<<<<<<< HEAD
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

=======
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
class AparaturaPomiarowaFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        // Pobierz pierwszego użytkownika jako creator
        $user = $manager->getRepository(User::class)->findOneBy([]);
        if (!$user) {
            throw new \Exception('No user found. Please load AppFixtures first.');
        }

        // Sprawdź czy moduł aparatury pomiarowej istnieje
        $moduleRepository = $manager->getRepository(\App\Entity\Module::class);
        $aparaturaModule = $moduleRepository->findOneBy(['name' => 'aparatura_pomiarowa']);
        
        if (!$aparaturaModule) {
            $aparaturaModule = new \App\Entity\Module();
            $aparaturaModule->setName('aparatura_pomiarowa')
                ->setDisplayName('Aparatura Pomiarowa')
                ->setDescription('Zarządzanie aparaturą pomiarową i miernikami')
                ->setRequiredPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'CALIBRATE']);
            $manager->persist($aparaturaModule);
            $manager->flush();
        }

        // Tworzenie 20 mierników i akcesoriów
        $equipment = [
            [
                'inventory_number' => 'AP-001-2024',
                'name' => 'Multimetr cyfrowy Fluke 87V',
                'description' => 'Multimetr przemysłowy z funkcją True RMS',
                'equipment_type' => 'multimeter',
                'manufacturer' => 'Fluke',
                'model' => '87V',
                'serial_number' => 'FL87V240001',
                'manufacturing_date' => '2023-08-15',
                'purchase_date' => '2024-01-15',
                'purchase_price' => 1850.00,
                'supplier' => 'ElektroTech Sp. z o.o.',
                'invoice_number' => 'ET/2024/001',
                'warranty_expiry' => '2027-01-15',
                'next_review_date' => '2025-01-15',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium A1',
                'notes' => 'Kalibracja co 12 miesięcy',
            ],
            [
                'inventory_number' => 'AP-002-2024',
                'name' => 'Oscyloskop Keysight DSOX1204G',
                'description' => 'Oscyloskop cyfrowy 4-kanałowy 200MHz',
                'equipment_type' => 'oscilloscope',
                'manufacturer' => 'Keysight',
                'model' => 'DSOX1204G',
                'serial_number' => 'MY55123456',
                'manufacturing_date' => '2023-07-20',
                'purchase_date' => '2024-02-10',
                'purchase_price' => 4850.00,
                'supplier' => 'Metra Instruments',
                'invoice_number' => 'MI/2024/034',
                'warranty_expiry' => '2027-02-10',
                'next_review_date' => '2025-02-10',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium A2',
                'notes' => 'Regularnie sprawdzać kalibrację',
            ],
            [
                'inventory_number' => 'AP-003-2024',
                'name' => 'Generator funkcyjny Rigol DG1032Z',
                'description' => 'Generator funkcyjny 30MHz 2-kanałowy',
                'equipment_type' => 'generator',
                'manufacturer' => 'Rigol',
                'model' => 'DG1032Z',
                'serial_number' => 'RG1032240012',
                'manufacturing_date' => '2023-09-10',
                'purchase_date' => '2024-01-20',
                'purchase_price' => 1250.00,
                'supplier' => 'Test Equipment Ltd',
                'invoice_number' => 'TE/2024/067',
                'warranty_expiry' => '2027-01-20',
                'next_review_date' => '2025-01-20',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium B1',
                'notes' => 'Sprawdzać stabilność częstotliwości',
            ],
            [
                'inventory_number' => 'AP-004-2024',
                'name' => 'Miernik LCR Hioki IM3536',
                'description' => 'Miernik pojemności, indukcyjności i rezystancji',
                'equipment_type' => 'lcr_meter',
                'manufacturer' => 'Hioki',
                'model' => 'IM3536',
                'serial_number' => 'HK3536240001',
                'manufacturing_date' => '2023-11-01',
                'purchase_date' => '2024-03-05',
                'purchase_price' => 3200.00,
                'supplier' => 'Hioki Poland',
                'invoice_number' => 'HP/2024/089',
                'warranty_expiry' => '2027-03-05',
                'next_review_date' => '2025-03-05',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium C1',
                'notes' => 'Kalibracja precyzyjna wymagana',
            ],
            [
                'inventory_number' => 'AP-005-2024',
                'name' => 'Zasilacz laboratoryjny Rigol DP832',
                'description' => 'Zasilacz 3-kanałowy 195W',
                'equipment_type' => 'power_supply',
                'manufacturer' => 'Rigol',
                'model' => 'DP832',
                'serial_number' => 'RG832240005',
                'manufacturing_date' => '2023-07-12',
                'purchase_date' => '2024-02-28',
                'purchase_price' => 980.00,
                'supplier' => 'Test Equipment Ltd',
                'invoice_number' => 'TE/2024/112',
                'warranty_expiry' => '2027-02-28',
                'next_review_date' => '2025-02-28',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Stanowisko 1',
                'notes' => 'Sprawdzać stabilność napięć',
            ],
            [
                'inventory_number' => 'AP-006-2024',
                'name' => 'Multimetr Keysight 34461A',
                'description' => 'Multimetr stolowy 6.5 cyfr',
                'equipment_type' => 'multimeter',
                'manufacturer' => 'Keysight',
                'model' => '34461A',
                'serial_number' => 'MY61A240001',
                'manufacturing_date' => '2023-10-15',
                'purchase_date' => '2024-04-12',
                'purchase_price' => 2650.00,
                'supplier' => 'Metra Instruments',
                'invoice_number' => 'MI/2024/156',
                'warranty_expiry' => '2027-04-12',
                'next_review_date' => '2025-04-12',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium A1',
                'notes' => 'Wysokiej precyzji pomiary',
            ],
            [
                'inventory_number' => 'AP-007-2024',
                'name' => 'Analizator widma Rigol DSA815',
                'description' => 'Analizator widma 1.5GHz',
                'equipment_type' => 'spectrum_analyzer',
                'manufacturer' => 'Rigol',
                'model' => 'DSA815',
                'serial_number' => 'RGDSA815240001',
                'manufacturing_date' => '2023-06-08',
                'purchase_date' => '2024-03-18',
                'purchase_price' => 5200.00,
                'supplier' => 'RF Solutions',
                'invoice_number' => 'RF/2024/078',
                'warranty_expiry' => '2027-03-18',
                'next_review_date' => '2025-03-18',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium RF',
                'notes' => 'Kalibracja co 12 miesięcy obowiązkowa',
            ],
            [
                'inventory_number' => 'AP-008-2024',
                'name' => 'Generator sygnału Keysight E4428C',
                'description' => 'Generator sygnału ESG 3GHz',
                'equipment_type' => 'generator',
                'manufacturer' => 'Keysight',
                'model' => 'E4428C',
                'serial_number' => 'MY428C240001',
                'manufacturing_date' => '2023-09-25',
                'purchase_date' => '2024-05-08',
                'purchase_price' => 8950.00,
                'supplier' => 'Keysight Direct',
                'invoice_number' => 'KD/2024/203',
                'warranty_expiry' => '2027-05-08',
                'next_review_date' => '2025-05-08',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium RF',
                'notes' => 'Urządzenie wysokiej klasy',
            ],
            [
                'inventory_number' => 'AP-009-2024',
                'name' => 'Miernik mocy Rohde&Schwarz NRP-Z21',
                'description' => 'Głowica pomiarowa mocy USB',
                'equipment_type' => 'power_meter',
                'manufacturer' => 'Rohde&Schwarz',
                'model' => 'NRP-Z21',
                'serial_number' => 'RS21240001',
                'manufacturing_date' => '2023-08-30',
                'purchase_date' => '2024-04-22',
                'purchase_price' => 1850.00,
                'supplier' => 'RS Poland',
                'invoice_number' => 'RSP/2024/134',
                'warranty_expiry' => '2027-04-22',
                'next_review_date' => '2025-04-22',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium RF',
                'notes' => 'Do pomiarów mocy RF',
            ],
            [
                'inventory_number' => 'AP-010-2024',
                'name' => 'Oscyloskop Rigol DS1104Z',
                'description' => 'Oscyloskop 4-kanałowy 100MHz',
                'equipment_type' => 'oscilloscope',
                'manufacturer' => 'Rigol',
                'model' => 'DS1104Z',
                'serial_number' => 'RG1104240010',
                'manufacturing_date' => '2023-12-01',
                'purchase_date' => '2024-05-15',
                'purchase_price' => 1450.00,
                'supplier' => 'Test Equipment Ltd',
                'invoice_number' => 'TE/2024/289',
                'warranty_expiry' => '2027-05-15',
                'next_review_date' => '2025-05-15',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium B2',
                'notes' => 'Do podstawowych pomiarów',
            ],
            [
                'inventory_number' => 'AP-011-2024',
                'name' => 'Kable pomiarowe BNC-BNC',
                'description' => 'Zestaw kabli BNC 50Ω różne długości',
                'equipment_type' => 'accessory',
                'manufacturer' => 'Pasternack',
                'model' => 'PE3001-XX',
                'serial_number' => 'PS3001SET01',
                'manufacturing_date' => '2023-11-15',
                'purchase_date' => '2024-06-01',
                'purchase_price' => 450.00,
                'supplier' => 'RF Cables Co',
                'invoice_number' => 'RFC/2024/345',
                'warranty_expiry' => '2026-06-01',
                'next_review_date' => '2025-06-01',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn akcesoriów',
                'notes' => 'Zestaw 10 kabli różnej długości',
            ],
            [
                'inventory_number' => 'AP-012-2024',
                'name' => 'Sondy oscyloskopowe 10:1',
                'description' => 'Sondy pasywne 100MHz',
                'equipment_type' => 'accessory',
                'manufacturer' => 'Keysight',
                'model' => 'N2862B',
                'serial_number' => 'MY862B240001',
                'manufacturing_date' => '2023-07-18',
                'purchase_date' => '2024-06-10',
                'purchase_price' => 380.00,
                'supplier' => 'Keysight Direct',
                'invoice_number' => 'KD/2024/401',
                'warranty_expiry' => '2026-06-10',
                'next_review_date' => '2025-06-10',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn akcesoriów',
                'notes' => 'Zestaw 4 sond',
            ],
            [
                'inventory_number' => 'AP-013-2024',
                'name' => 'Kaliber do pomiarów Fluke 5500A',
                'description' => 'Kaliber wielofunkcyjny',
                'equipment_type' => 'calibrator',
                'manufacturer' => 'Fluke',
                'model' => '5500A',
                'serial_number' => 'FL5500A240001',
                'manufacturing_date' => '2023-05-22',
                'purchase_date' => '2024-07-03',
                'purchase_price' => 12500.00,
                'supplier' => 'Fluke Authorized',
                'invoice_number' => 'FA/2024/567',
                'warranty_expiry' => '2027-07-03',
                'next_review_date' => '2025-07-03',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Pracownia wzorcowania',
                'notes' => 'Urządzenie wzorcujące',
            ],
            [
                'inventory_number' => 'AP-014-2024',
                'name' => 'Miernik impedancji Keysight E4991B',
                'description' => 'Analizator impedancji 3GHz',
                'equipment_type' => 'impedance_analyzer',
                'manufacturer' => 'Keysight',
                'model' => 'E4991B',
                'serial_number' => 'MY991B240001',
                'manufacturing_date' => '2023-10-08',
                'purchase_date' => '2024-07-20',
                'purchase_price' => 25000.00,
                'supplier' => 'Keysight Direct',
                'invoice_number' => 'KD/2024/789',
                'warranty_expiry' => '2027-07-20',
                'next_review_date' => '2025-07-20',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium precyzyjne',
                'notes' => 'Najwyższa klasa dokładności',
            ],
            [
                'inventory_number' => 'AP-015-2024',
                'name' => 'Termostat cyfrowy Fluke 7341',
                'description' => 'Łaźnia termostatyczna do kalibracji',
                'equipment_type' => 'calibrator',
                'manufacturer' => 'Fluke',
                'model' => '7341',
                'serial_number' => 'FL7341240001',
                'manufacturing_date' => '2023-06-12',
                'purchase_date' => '2024-08-05',
                'purchase_price' => 8950.00,
                'supplier' => 'Fluke Authorized',
                'invoice_number' => 'FA/2024/901',
                'warranty_expiry' => '2027-08-05',
                'next_review_date' => '2025-08-05',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Pracownia wzorcowania',
                'notes' => 'Do kalibracji temperaturowej',
            ],
            [
                'inventory_number' => 'AP-016-2024',
                'name' => 'Adapter N-SMA zestaw',
                'description' => 'Adaptery przejściowe różne typy',
                'equipment_type' => 'accessory',
                'manufacturer' => 'Amphenol',
                'model' => 'ADP-SET-01',
                'serial_number' => 'AM01SET240001',
                'manufacturing_date' => '2023-09-01',
                'purchase_date' => '2024-08-15',
                'purchase_price' => 650.00,
                'supplier' => 'Connector World',
                'invoice_number' => 'CW/2024/123',
                'warranty_expiry' => '2026-08-15',
                'next_review_date' => '2025-08-15',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn akcesoriów',
                'notes' => 'Zestaw 20 adapterów',
            ],
            [
                'inventory_number' => 'AP-017-2024',
                'name' => 'Atten ATB-002',
                'description' => 'Ławka montażowa z zasilaniem',
                'equipment_type' => 'accessory',
                'manufacturer' => 'ATTEN',
                'model' => 'ATB-002',
                'serial_number' => 'AT002240001',
                'manufacturing_date' => '2023-08-20',
                'purchase_date' => '2024-09-01',
                'purchase_price' => 1200.00,
                'supplier' => 'Workshop Supply',
                'invoice_number' => 'WS/2024/234',
                'warranty_expiry' => '2026-09-01',
                'next_review_date' => '2025-09-01',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Stanowisko montażowe',
                'notes' => 'Ze zintegrowanym zasilaniem',
            ],
            [
                'inventory_number' => 'AP-018-2024',
                'name' => 'Multimetr kieszonkowy Fluke 101',
                'description' => 'Kompaktowy multimetr podstawowy',
                'equipment_type' => 'multimeter',
                'manufacturer' => 'Fluke',
                'model' => '101',
                'serial_number' => 'FL101240018',
                'manufacturing_date' => '2023-11-20',
                'purchase_date' => '2024-09-10',
                'purchase_price' => 280.00,
                'supplier' => 'ElektroTech Sp. z o.o.',
                'invoice_number' => 'ET/2024/456',
                'warranty_expiry' => '2026-09-10',
                'next_review_date' => '2025-09-10',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Zestaw mobilny',
                'notes' => 'Do szybkich pomiarów w terenie',
            ],
            [
                'inventory_number' => 'AP-019-2024',
                'name' => 'Częstościomierz Rigol DM3058E',
                'description' => 'Częstościomierz 5.5 cyfr',
                'equipment_type' => 'frequency_counter',
                'manufacturer' => 'Rigol',
                'model' => 'DM3058E',
                'serial_number' => 'RG3058240019',
                'manufacturing_date' => '2023-07-30',
                'purchase_date' => '2024-09-25',
                'purchase_price' => 850.00,
                'supplier' => 'Test Equipment Ltd',
                'invoice_number' => 'TE/2024/678',
                'warranty_expiry' => '2026-09-25',
                'next_review_date' => '2025-09-25',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Laboratorium B1',
                'notes' => 'Pomiary częstotliwości',
            ],
            [
                'inventory_number' => 'AP-020-2024',
                'name' => 'Walizka transportowa Peli 1550',
                'description' => 'Walizka ochronna z wkładką pianową',
                'equipment_type' => 'accessory',
                'manufacturer' => 'Pelican',
                'model' => '1550',
                'serial_number' => 'PE1550240020',
                'manufacturing_date' => '2023-12-10',
                'purchase_date' => '2024-10-05',
                'purchase_price' => 320.00,
                'supplier' => 'Protection Cases',
                'invoice_number' => 'PC/2024/890',
                'warranty_expiry' => '2026-10-05',
                'next_review_date' => '2025-10-05',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn akcesoriów',
                'notes' => 'Do transportu delikatnego sprzętu',
            ],
        ];

        $equipmentObjects = [];
        foreach ($equipment as $data) {
            $equipmentObj = new AparaturaPomiarowaEquipment();
            $equipmentObj->setInventoryNumber($data['inventory_number']);
            $equipmentObj->setName($data['name']);
            $equipmentObj->setDescription($data['description']);
            $equipmentObj->setEquipmentType($data['equipment_type']);
            $equipmentObj->setManufacturer($data['manufacturer']);
            $equipmentObj->setModel($data['model']);
            $equipmentObj->setSerialNumber($data['serial_number']);
            $equipmentObj->setManufacturingDate(new \DateTime($data['manufacturing_date']));
            $equipmentObj->setPurchaseDate(new \DateTime($data['purchase_date']));
            $equipmentObj->setPurchasePrice($data['purchase_price']);
            $equipmentObj->setSupplier($data['supplier']);
            $equipmentObj->setInvoiceNumber($data['invoice_number']);
            $equipmentObj->setWarrantyExpiry(new \DateTime($data['warranty_expiry']));
            $equipmentObj->setNextReviewDate(new \DateTime($data['next_review_date']));
            $equipmentObj->setReviewIntervalMonths($data['review_interval_months']);
            $equipmentObj->setStatus($data['status']);
            $equipmentObj->setLocation($data['location']);
            $equipmentObj->setNotes($data['notes']);
            $equipmentObj->setCreatedBy($user);

            $manager->persist($equipmentObj);
            $equipmentObjects[] = $equipmentObj;
        }

        // Tworzenie 5 zestawów sprzętu
        $equipmentSets = [
            [
                'name' => 'Zestaw Elektroniczny Podstawowy',
                'description' => 'Podstawowy zestaw mierników do elektroniki',
                'set_type' => 'basic',
                'location' => 'Laboratorium A',
                'notes' => 'Zestaw dla początkujących techników',
                'equipment_indices' => [0, 2, 4, 17], // Multimetr Fluke 87V, Generator Rigol, Zasilacz Rigol, Multimetr kieszonkowy
            ],
            [
                'name' => 'Zestaw RF/Mikrofalowy',
                'description' => 'Zestaw do pomiarów RF i sygnałów wysokiej częstotliwości',
                'set_type' => 'advanced',
                'location' => 'Laboratorium RF',
                'notes' => 'Do zaawansowanych pomiarów RF',
                'equipment_indices' => [6, 7, 8, 10, 15], // Analizator widma, Generator ESG, Miernik mocy, Kable BNC, Adaptery N-SMA
            ],
            [
                'name' => 'Zestaw Wzorcujący',
                'description' => 'Zestaw do kalibracji i wzorcowania',
                'set_type' => 'specialist',
                'location' => 'Pracownia wzorcowania',
                'notes' => 'Najwyższej klasy sprzęt metrologiczny',
                'equipment_indices' => [5, 12, 13, 14], // Multimetr Keysight 34461A, Kaliber Fluke 5500A, Analizator impedancji, Termostat
            ],
            [
                'name' => 'Zestaw Mobilny Serwisowy',
                'description' => 'Kompaktowy zestaw do prac serwisowych w terenie',
                'set_type' => 'rescue',
                'location' => 'Zestaw mobilny',
                'notes' => 'W walizce transportowej, gotowy do wyjazdu',
                'equipment_indices' => [17, 18, 11, 19], // Multimetr kieszonkowy, Częstościomierz, Sondy, Walizka
            ],
            [
                'name' => 'Zestaw Dydaktyczny Podstawowy',
                'description' => 'Zestaw do zajęć edukacyjnych i szkoleń',
                'set_type' => 'basic',
                'location' => 'Laboratorium B',
                'notes' => 'Do nauczania podstaw elektroniki',
                'equipment_indices' => [1, 9, 16], // Oscyloskop Keysight, Oscyloskop Rigol, Ławka montażowa
            ],
        ];

        foreach ($equipmentSets as $setData) {
            $equipmentSet = new AparaturaPomiarowaEquipmentSet();
            $equipmentSet->setName($setData['name']);
            $equipmentSet->setDescription($setData['description']);
            $equipmentSet->setSetType($setData['set_type']);
            $equipmentSet->setLocation($setData['location']);
            $equipmentSet->setNotes($setData['notes']);
            $equipmentSet->setCreatedBy($user);

            // Dodaj sprzęt do zestawu
            foreach ($setData['equipment_indices'] as $index) {
                if (isset($equipmentObjects[$index])) {
                    $equipmentSet->addEquipment($equipmentObjects[$index]);
                }
            }

            $manager->persist($equipmentSet);
        }

        $manager->flush();
    }


    public static function getGroups(): array
    {
        return ['aparatura-pomiarowa'];
    }
>>>>>>> 87c92e45a232ceec51ed82c78ea506b35914d032
}