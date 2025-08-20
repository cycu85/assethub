<?php

namespace App\Service;

use App\Entity\Equipment;
use App\Entity\EquipmentCategory;
use App\Entity\EquipmentLog;
use App\Entity\User;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SampleDataService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function loadSampleData(): void
    {
        // First load all fixtures (including asekuracja module data)
        $fixtures = new AppFixtures($this->passwordHasher);
        $fixtures->load($this->entityManager);
        
        // Then load additional sample data
        $this->createSampleUsers();
        $this->createSampleEquipment();
        $this->entityManager->flush();
    }

    private function createSampleUsers(): array
    {
        $users = [
            [
                'username' => 'j.kowalski',
                'email' => 'jan.kowalski@firma.pl',
                'firstName' => 'Jan',
                'lastName' => 'Kowalski',
                'employeeNumber' => 'EMP001',
                'position' => 'Kierownik Budowy',
                'department' => 'Dział Produkcji',
                'phoneNumber' => '+48 123 456 789',
                'password' => 'haslo123'
            ],
            [
                'username' => 'a.nowak',
                'email' => 'anna.nowak@firma.pl',
                'firstName' => 'Anna',
                'lastName' => 'Nowak',
                'employeeNumber' => 'EMP002',
                'position' => 'Specjalista BHP',
                'department' => 'Dział BHP',
                'phoneNumber' => '+48 123 456 790',
                'password' => 'haslo123'
            ],
            [
                'username' => 'm.wisniewski',
                'email' => 'marek.wisniewski@firma.pl',
                'firstName' => 'Marek',
                'lastName' => 'Wiśniewski',
                'employeeNumber' => 'EMP003',
                'position' => 'Operator Maszyn',
                'department' => 'Dział Produkcji',
                'phoneNumber' => '+48 123 456 791',
                'password' => 'haslo123'
            ],
            [
                'username' => 'k.zielinska',
                'email' => 'katarzyna.zielinska@firma.pl',
                'firstName' => 'Katarzyna',
                'lastName' => 'Zielińska',
                'employeeNumber' => 'EMP004',
                'position' => 'Magazynier',
                'department' => 'Magazyn',
                'phoneNumber' => '+48 123 456 792',
                'password' => 'haslo123'
            ],
            [
                'username' => 'p.kaminski',
                'email' => 'piotr.kaminski@firma.pl',
                'firstName' => 'Piotr',
                'lastName' => 'Kamiński',
                'employeeNumber' => 'EMP005',
                'position' => 'Serwisant',
                'department' => 'Dział Techniczny',
                'phoneNumber' => '+48 123 456 793',
                'password' => 'haslo123'
            ]
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmployeeNumber($userData['employeeNumber']);
            $user->setPosition($userData['position']);
            $user->setDepartment($userData['department']);
            $user->setPhoneNumber($userData['phoneNumber']);
            $user->setIsActive(true);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $createdUsers[] = $user;
        }

        return $createdUsers;
    }

    private function createSampleEquipment(): void
    {
        $categories = $this->entityManager->getRepository(EquipmentCategory::class)->findAll();
        $users = $this->entityManager->getRepository(User::class)->findAll();
        
        if (empty($categories) || empty($users)) {
            return;
        }

        $equipmentData = [
            [
                'inventoryNumber' => 'EQ-001',
                'name' => 'Młotek pneumatyczny Bosch GSH 11 VC',
                'description' => 'Profesjonalny młotek pneumatyczny do prac rozbiórkowych. Wyposażony w system SDS-max.',
                'manufacturer' => 'Bosch',
                'model' => 'GSH 11 VC',
                'serialNumber' => 'BSH2023001',
                'purchaseDate' => new \DateTime('-18 months'),
                'purchasePrice' => '2899.00',
                'status' => Equipment::STATUS_IN_USE,
                'location' => 'Magazyn A - Regał 1',
                'warrantyExpiry' => new \DateTime('+6 months'),
                'nextInspectionDate' => new \DateTime('+2 months'),
                'notes' => 'Urządzenie w bardzo dobrym stanie. Ostatni serwis przeprowadzony 3 miesiące temu.',
                'categoryIndex' => 1 // Narzędzia elektryczne
            ],
            [
                'inventoryNumber' => 'EQ-002',
                'name' => 'Wiertarka udarowa Makita HP2050',
                'description' => 'Wiertarka udarowa 13mm z regulacją momentu obrotowego.',
                'manufacturer' => 'Makita',
                'model' => 'HP2050',
                'serialNumber' => 'MKT2023002',
                'purchaseDate' => new \DateTime('-12 months'),
                'purchasePrice' => '459.00',
                'status' => Equipment::STATUS_AVAILABLE,
                'location' => 'Magazyn A - Regał 2',
                'warrantyExpiry' => new \DateTime('+12 months'),
                'nextInspectionDate' => new \DateTime('+6 months'),
                'notes' => 'Nowa wiertarka, użyta tylko kilka razy.',
                'categoryIndex' => 1
            ],
            [
                'inventoryNumber' => 'EQ-003',
                'name' => 'Klucz dynamometryczny 10-50 Nm',
                'description' => 'Precyzyjny klucz dynamometryczny z certyfikatem kalibracji.',
                'manufacturer' => 'Gedore',
                'model' => 'DREMOSTAR',
                'serialNumber' => 'GED2023003',
                'purchaseDate' => new \DateTime('-24 months'),
                'purchasePrice' => '890.00',
                'status' => Equipment::STATUS_MAINTENANCE,
                'location' => 'Serwis zewnętrzny',
                'warrantyExpiry' => new \DateTime('-12 months'),
                'nextInspectionDate' => new \DateTime('+1 month'),
                'notes' => 'Klucz w serwisie na kalibracji. Planowany odbiór za tydzień.',
                'categoryIndex' => 0 // Narzędzia ręczne
            ],
            [
                'inventoryNumber' => 'EQ-004',
                'name' => 'Szlifierka kątowa 125mm Hilti AG 125-A22',
                'description' => 'Bezprzewodowa szlifierka kątowa z systemem antywibracjnym.',
                'manufacturer' => 'Hilti',
                'model' => 'AG 125-A22',
                'serialNumber' => 'HLT2023004',
                'purchaseDate' => new \DateTime('-6 months'),
                'purchasePrice' => '1250.00',
                'status' => Equipment::STATUS_IN_USE,
                'location' => 'Plac budowy - Obiekt A',
                'warrantyExpiry' => new \DateTime('+18 months'),
                'nextInspectionDate' => new \DateTime('+3 months'),
                'notes' => 'Szlifierka używana na bieżącym projekcie.',
                'categoryIndex' => 1
            ],
            [
                'inventoryNumber' => 'EQ-005',
                'name' => 'Poziomica laserowa Bosch GLL 3-80',
                'description' => 'Poziomica laserowa z trzema liniami i funkcją samopozwalania.',
                'manufacturer' => 'Bosch',
                'model' => 'GLL 3-80',
                'serialNumber' => 'BSH2023005',
                'purchaseDate' => new \DateTime('-15 months'),
                'purchasePrice' => '1650.00',
                'status' => Equipment::STATUS_AVAILABLE,
                'location' => 'Magazyn B - Szafa 1',
                'warrantyExpiry' => new \DateTime('+9 months'),
                'nextInspectionDate' => new \DateTime('+4 months'),
                'notes' => 'Poziomica po konserwacji, gotowa do użycia.',
                'categoryIndex' => 2 // Sprzęt pomiarowy
            ],
            [
                'inventoryNumber' => 'EQ-006',
                'name' => 'Kask ochronny JSP EVO2',
                'description' => 'Kask ochronny z regulacją obwodu głowy, wentylowany.',
                'manufacturer' => 'JSP',
                'model' => 'EVO2',
                'serialNumber' => 'JSP2023006',
                'purchaseDate' => new \DateTime('-8 months'),
                'purchasePrice' => '125.00',
                'status' => Equipment::STATUS_IN_USE,
                'location' => 'Przypisany do pracownika',
                'warrantyExpiry' => new \DateTime('+16 months'),
                'nextInspectionDate' => new \DateTime('+4 months'),
                'notes' => 'Kask w użyciu zgodnie z procedurami BHP.',
                'categoryIndex' => 3 // Sprzęt ochronny
            ],
            [
                'inventoryNumber' => 'EQ-007',
                'name' => 'Piła tarczowa Festool TS 55 REQ',
                'description' => 'Piła tarczowa z szyną prowadzącą, system odpylania.',
                'manufacturer' => 'Festool',
                'model' => 'TS 55 REQ',
                'serialNumber' => 'FST2023007',
                'purchaseDate' => new \DateTime('-20 months'),
                'purchasePrice' => '2100.00',
                'status' => Equipment::STATUS_REPAIR,
                'location' => 'Warsztat serwisowy',
                'warrantyExpiry' => new \DateTime('-8 months'),
                'nextInspectionDate' => new \DateTime('+2 weeks'),
                'notes' => 'Piła w naprawie - wymiana szczotek węglowych w silniku.',
                'categoryIndex' => 1
            ],
            [
                'inventoryNumber' => 'EQ-008',
                'name' => 'Multimetr cyfrowy Fluke 87V',
                'description' => 'Profesjonalny multimetr przemysłowy z funkcją TrueRMS.',
                'manufacturer' => 'Fluke',
                'model' => '87V',
                'serialNumber' => 'FLK2023008',
                'purchaseDate' => new \DateTime('-30 months'),
                'purchasePrice' => '1450.00',
                'status' => Equipment::STATUS_AVAILABLE,
                'location' => 'Magazyn C - Szuflada 3',
                'warrantyExpiry' => new \DateTime('-6 months'),
                'nextInspectionDate' => new \DateTime('+6 months'),
                'notes' => 'Multimetr skalibrowany, wyniki kalibracji w dokumentacji.',
                'categoryIndex' => 2
            ],
            [
                'inventoryNumber' => 'EQ-009',
                'name' => 'Rękawice robocze Uvex Phynomic C3',
                'description' => 'Rękawice robocze z powłoką nitrylową, rozmiar 9.',
                'manufacturer' => 'Uvex',
                'model' => 'Phynomic C3',
                'serialNumber' => 'UVX2023009',
                'purchaseDate' => new \DateTime('-4 months'),
                'purchasePrice' => '25.50',
                'status' => Equipment::STATUS_IN_USE,
                'location' => 'Przypisane do pracownika',
                'warrantyExpiry' => new \DateTime('+8 months'),
                'nextInspectionDate' => new \DateTime('+2 months'),
                'notes' => 'Rękawice w codziennym użyciu, stan dobry.',
                'categoryIndex' => 3
            ],
            [
                'inventoryNumber' => 'EQ-010',
                'name' => 'Drabina aluminiowa 3x12 szczebli',
                'description' => 'Drabina wielofunkcyjna aluminiowa z certyfikatem EN 131.',
                'manufacturer' => 'Krause',
                'model' => 'Corda',
                'serialNumber' => 'KRS2023010',
                'purchaseDate' => new \DateTime('-14 months'),
                'purchasePrice' => '1850.00',
                'status' => Equipment::STATUS_AVAILABLE,
                'location' => 'Magazyn A - Strefa drabin',
                'warrantyExpiry' => new \DateTime('+10 months'),
                'nextInspectionDate' => new \DateTime('+2 months'),
                'notes' => 'Drabina sprawna, ostatni przegląd techniczny przeprowadzony przed miesiącem.',
                'categoryIndex' => 0
            ]
        ];

        foreach ($equipmentData as $index => $data) {
            $equipment = new Equipment();
            $equipment->setInventoryNumber($data['inventoryNumber']);
            $equipment->setName($data['name']);
            $equipment->setDescription($data['description']);
            $equipment->setManufacturer($data['manufacturer']);
            $equipment->setModel($data['model']);
            $equipment->setSerialNumber($data['serialNumber']);
            $equipment->setPurchaseDate($data['purchaseDate']);
            $equipment->setPurchasePrice($data['purchasePrice']);
            $equipment->setStatus($data['status']);
            $equipment->setLocation($data['location']);
            $equipment->setWarrantyExpiry($data['warrantyExpiry']);
            $equipment->setNextInspectionDate($data['nextInspectionDate']);
            $equipment->setNotes($data['notes']);

            // Assign category
            if (isset($categories[$data['categoryIndex']])) {
                $equipment->setCategory($categories[$data['categoryIndex']]);
            }

            // Assign to user for IN_USE items
            if ($data['status'] === Equipment::STATUS_IN_USE) {
                $randomUser = $users[array_rand($users)];
                $equipment->setAssignedTo($randomUser);
            }

            // Set creator (use first admin user)
            $adminUser = $users[0] ?? null;
            if ($adminUser) {
                $equipment->setCreatedBy($adminUser);
            }

            $this->entityManager->persist($equipment);

            // Create initial log entry
            if ($adminUser) {
                $log = new EquipmentLog();
                $log->setEquipment($equipment);
                $log->setAction(EquipmentLog::ACTION_CREATED);
                $log->setDescription('Sprzęt dodany do systemu podczas importu danych przykładowych');
                $log->setCreatedBy($adminUser);
                $this->entityManager->persist($log);

                // Add assignment log for IN_USE items
                if ($data['status'] === Equipment::STATUS_IN_USE && $equipment->getAssignedTo()) {
                    $assignLog = new EquipmentLog();
                    $assignLog->setEquipment($equipment);
                    $assignLog->setAction(EquipmentLog::ACTION_ASSIGNED);
                    $assignLog->setDescription('Sprzęt przypisany do użytkownika');
                    $assignLog->setNewAssignee($equipment->getAssignedTo());
                    $assignLog->setCreatedBy($adminUser);
                    $this->entityManager->persist($assignLog);
                }
            }
        }
    }

    public function getSampleDataSummary(): array
    {
        return [
            'users' => 5,
            'equipment' => 10,
            'categories' => 4,
            'logs' => 'Automatycznie generowane dla każdego sprzętu'
        ];
    }
}