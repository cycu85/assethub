<?php

namespace App\DataFixtures;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
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
}