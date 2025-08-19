<?php

namespace App\DataFixtures;

use App\Entity\Dictionary;
use App\Entity\Module;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\AsekuracyjnySPM\Entity\AsekuracijnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracijnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracijnyEquipmentSetItem;
use App\AsekuracyjnySPM\Entity\AsekuracijnyReview;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Check if modules already exist
        $moduleRepository = $manager->getRepository(Module::class);
        $adminModule = $moduleRepository->findOneBy(['name' => 'admin']);
        $equipmentModule = $moduleRepository->findOneBy(['name' => 'equipment']);
        $asekuracyjnyModule = $moduleRepository->findOneBy(['name' => 'asekuracja']);

        // Create modules only if they don't exist
        if (!$adminModule) {
            $adminModule = new Module();
            $adminModule->setName('admin')
                ->setDisplayName('Administracja')
                ->setDescription('Panel administracyjny systemu')
                ->setRequiredPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'CONFIGURE', 'EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC', 'EMPLOYEES_EDIT_FULL']);
            $manager->persist($adminModule);
        }

        if (!$equipmentModule) {
            $equipmentModule = new Module();
            $equipmentModule->setName('equipment')
                ->setDisplayName('Sprzęt wysokościowy')
                ->setDescription('Zarządzanie sprzętem wysokościowym')
                ->setRequiredPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'EXPORT']);
            $manager->persist($equipmentModule);
        }

        if (!$asekuracyjnyModule) {
            $asekuracyjnyModule = new Module();
            $asekuracyjnyModule->setName('asekuracja')
                ->setDisplayName('Asekuracja')
                ->setDescription('Zarządzanie sprzętem asekuracyjnym/wysokościowym')
                ->setRequiredPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'TRANSFER']);
            $manager->persist($asekuracyjnyModule);
        }

        // Create roles only if they don't exist
        $roleRepository = $manager->getRepository(Role::class);
        $adminRole = $roleRepository->findOneBy(['name' => 'system_admin']);
        
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setName('system_admin')
                ->setDescription('Administrator systemu')
                ->setModule($adminModule)
                ->setPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'CONFIGURE', 'EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC', 'EMPLOYEES_EDIT_FULL'])
                ->setIsSystemRole(true);
            $manager->persist($adminRole);
        }

        // Employee management roles
        $employeesViewRole = $roleRepository->findOneBy(['name' => 'employees_viewer']);
        if (!$employeesViewRole) {
            $employeesViewRole = new Role();
            $employeesViewRole->setName('employees_viewer')
                ->setDescription('Przeglądanie listy pracowników')
                ->setModule($adminModule)
                ->setPermissions(['EMPLOYEES_VIEW'])
                ->setIsSystemRole(true);
            $manager->persist($employeesViewRole);
        }

        $employeesEditorRole = $roleRepository->findOneBy(['name' => 'employees_editor']);
        if (!$employeesEditorRole) {
            $employeesEditorRole = new Role();
            $employeesEditorRole->setName('employees_editor')
                ->setDescription('Edycja podstawowych danych pracowników')
                ->setModule($adminModule)
                ->setPermissions(['EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC'])
                ->setIsSystemRole(true);
            $manager->persist($employeesEditorRole);
        }

        $employeesManagerRole = $roleRepository->findOneBy(['name' => 'employees_manager']);
        if (!$employeesManagerRole) {
            $employeesManagerRole = new Role();
            $employeesManagerRole->setName('employees_manager')
                ->setDescription('Pełne zarządzanie pracownikami')
                ->setModule($adminModule)
                ->setPermissions(['EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC', 'EMPLOYEES_EDIT_FULL'])
                ->setIsSystemRole(true);
            $manager->persist($employeesManagerRole);
        }

        $equipmentManagerRole = $roleRepository->findOneBy(['name' => 'equipment_manager']);
        if (!$equipmentManagerRole) {
            $equipmentManagerRole = new Role();
            $equipmentManagerRole->setName('equipment_manager')
                ->setDescription('Menedżer sprzętu wysokościowego')
                ->setModule($equipmentModule)
                ->setPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'EXPORT'])
                ->setIsSystemRole(true);
            $manager->persist($equipmentManagerRole);
        }

        $equipmentViewerRole = $roleRepository->findOneBy(['name' => 'equipment_viewer']);
        if (!$equipmentViewerRole) {
            $equipmentViewerRole = new Role();
            $equipmentViewerRole->setName('equipment_viewer')
                ->setDescription('Przeglądanie sprzętu wysokościowego')
                ->setModule($equipmentModule)
                ->setPermissions(['VIEW'])
                ->setIsSystemRole(true);
            $manager->persist($equipmentViewerRole);
        }

        // Asekuracja roles
        $assekAdminRole = $roleRepository->findOneBy(['name' => 'ASSEK_ADMIN']);
        if (!$assekAdminRole) {
            $assekAdminRole = new Role();
            $assekAdminRole->setName('ASSEK_ADMIN')
                ->setDescription('Administrator Asekuracji - pełne prawa do modułu')
                ->setModule($asekuracyjnyModule)
                ->setPermissions(['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'TRANSFER'])
                ->setIsSystemRole(false);
            $manager->persist($assekAdminRole);
        }

        $assekEditorRole = $roleRepository->findOneBy(['name' => 'ASSEK_EDITOR']);
        if (!$assekEditorRole) {
            $assekEditorRole = new Role();
            $assekEditorRole->setName('ASSEK_EDITOR')
                ->setDescription('Edytor Asekuracji - bez uprawnień do usuwania')
                ->setModule($asekuracyjnyModule)
                ->setPermissions(['VIEW', 'CREATE', 'EDIT', 'ASSIGN', 'REVIEW', 'TRANSFER'])
                ->setIsSystemRole(false);
            $manager->persist($assekEditorRole);
        }

        $assekViewerRole = $roleRepository->findOneBy(['name' => 'ASSEK_VIEWER']);
        if (!$assekViewerRole) {
            $assekViewerRole = new Role();
            $assekViewerRole->setName('ASSEK_VIEWER')
                ->setDescription('Przeglądający Asekuracji - tylko podgląd')
                ->setModule($asekuracyjnyModule)
                ->setPermissions(['VIEW'])
                ->setIsSystemRole(false);
            $manager->persist($assekViewerRole);
        }

        $assekListRole = $roleRepository->findOneBy(['name' => 'ASSEK_LIST']);
        if (!$assekListRole) {
            $assekListRole = new Role();
            $assekListRole->setName('ASSEK_LIST')
                ->setDescription('Lista Asekuracji - tylko lista zestawów i elementów')
                ->setModule($asekuracyjnyModule)
                ->setPermissions(['VIEW_LIST'])
                ->setIsSystemRole(false);
            $manager->persist($assekListRole);
        }

        // Create users only if they don't exist
        $userRepository = $manager->getRepository(User::class);
        
        $adminUser = $userRepository->findOneBy(['username' => 'admin']);
        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setUsername('admin')
                ->setEmail('admin@assethub.local')
                ->setFirstName('Administrator')
                ->setLastName('Systemu')
                ->setPosition('Administrator')
                ->setDepartment('IT');

            $hashedPassword = $this->passwordHasher->hashPassword($adminUser, 'admin123');
            $adminUser->setPassword($hashedPassword);
            $manager->persist($adminUser);
        }

        $testUser = $userRepository->findOneBy(['username' => 'user']);
        if (!$testUser) {
            $testUser = new User();
            $testUser->setUsername('user')
                ->setEmail('user@assethub.local')
                ->setFirstName('Jan')
                ->setLastName('Kowalski')
                ->setEmployeeNumber('EMP001')
                ->setPosition('Pracownik')
                ->setDepartment('Produkcja');

            $hashedPassword = $this->passwordHasher->hashPassword($testUser, 'user123');
            $testUser->setPassword($hashedPassword);
            $manager->persist($testUser);
        }

        $hrUser = $userRepository->findOneBy(['username' => 'hr']);
        if (!$hrUser) {
            $hrUser = new User();
            $hrUser->setUsername('hr')
                ->setEmail('hr@assethub.local')
                ->setFirstName('Anna')
                ->setLastName('Nowak')
                ->setEmployeeNumber('EMP002')
                ->setPosition('Specjalista ds. kadr')
                ->setDepartment('HR');

            $hashedPassword = $this->passwordHasher->hashPassword($hrUser, 'hr123');
            $hrUser->setPassword($hashedPassword);
            $manager->persist($hrUser);
        }

        $manager->flush();

        // Assign roles to users
        $adminUserRole = new UserRole();
        $adminUserRole->setUser($adminUser)
            ->setRole($adminRole)
            ->setAssignedBy($adminUser);
        $manager->persist($adminUserRole);

        $equipmentAdminRole = new UserRole();
        $equipmentAdminRole->setUser($adminUser)
            ->setRole($equipmentManagerRole)
            ->setAssignedBy($adminUser);
        $manager->persist($equipmentAdminRole);

        $assekAdminUserRole = new UserRole();
        $assekAdminUserRole->setUser($adminUser)
            ->setRole($assekAdminRole)
            ->setAssignedBy($adminUser);
        $manager->persist($assekAdminUserRole);

        $testUserRole = new UserRole();
        $testUserRole->setUser($testUser)
            ->setRole($equipmentViewerRole)
            ->setAssignedBy($adminUser);
        $manager->persist($testUserRole);

        // Assign HR role to HR user
        $hrUserRole = new UserRole();
        $hrUserRole->setUser($hrUser)
            ->setRole($employeesEditorRole)
            ->setAssignedBy($adminUser);
        $manager->persist($hrUserRole);

        $manager->flush();

        // Create employee dictionaries
        $this->createEmployeeDictionaries($manager);

        // Create asekuracja dictionaries
        $this->createAsekuracijnyDictionaries($manager);

        // Create example asekuracyjny equipment and sets
        $this->createAsekuracyjnyExampleData($manager, $adminUser);

        // Update users with example data
        $this->updateUsersWithExampleData($manager, $adminUser, $testUser, $hrUser);

        $manager->flush();
    }

    private function createEmployeeDictionaries(ObjectManager $manager): void
    {
        $dictionaryRepository = $manager->getRepository(Dictionary::class);
        
        // Check if employee dictionaries already exist
        if ($dictionaryRepository->findOneBy(['type' => 'employee_branches'])) {
            return; // Skip if dictionaries already exist
        }
        
        // Employee branches (oddziały)
        $branches = [
            ['name' => 'Oddział Główny', 'value' => 'main_branch', 'description' => 'Główna siedziba firmy'],
            ['name' => 'Oddział Warszawa', 'value' => 'warsaw_branch', 'description' => 'Oddział w Warszawie'],
            ['name' => 'Oddział Kraków', 'value' => 'krakow_branch', 'description' => 'Oddział w Krakowie'],
            ['name' => 'Oddział Gdańsk', 'value' => 'gdansk_branch', 'description' => 'Oddział w Gdańsku'],
            ['name' => 'Oddział Wrocław', 'value' => 'wroclaw_branch', 'description' => 'Oddział we Wrocławiu'],
        ];

        foreach ($branches as $index => $branchData) {
            $branch = new Dictionary();
            $branch->setType('employee_branches')
                ->setName($branchData['name'])
                ->setValue($branchData['value'])
                ->setDescription($branchData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor('#405189')
                ->setIcon('ri-building-line');
            $manager->persist($branch);
        }

        // Employee statuses (statusy pracowników)
        $statuses = [
            ['name' => 'Aktywny', 'value' => 'active', 'description' => 'Pracownik aktywny', 'color' => '#28a745'],
            ['name' => 'Nieaktywny', 'value' => 'inactive', 'description' => 'Pracownik nieaktywny', 'color' => '#6c757d'],
            ['name' => 'Urlop', 'value' => 'on_leave', 'description' => 'Pracownik na urlopie', 'color' => '#ffc107'],
            ['name' => 'Zwolnienie lekarskie', 'value' => 'sick_leave', 'description' => 'Pracownik na zwolnieniu', 'color' => '#fd7e14'],
            ['name' => 'Wypowiedzenie', 'value' => 'notice_period', 'description' => 'Pracownik w okresie wypowiedzenia', 'color' => '#dc3545'],
            ['name' => 'Próbny', 'value' => 'probation', 'description' => 'Pracownik w okresie próbnym', 'color' => '#17a2b8'],
        ];

        foreach ($statuses as $index => $statusData) {
            $status = new Dictionary();
            $status->setType('employee_statuses')
                ->setName($statusData['name'])
                ->setValue($statusData['value'])
                ->setDescription($statusData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor($statusData['color'])
                ->setIcon('ri-user-line');
            $manager->persist($status);
        }

        // Employee departments (działy pracowników)
        $departments = [
            ['name' => 'Dział IT', 'value' => 'it_department', 'description' => 'Dział informatyczny'],
            ['name' => 'Dział HR', 'value' => 'hr_department', 'description' => 'Dział zasobów ludzkich'],
            ['name' => 'Dział Księgowy', 'value' => 'accounting_department', 'description' => 'Dział księgowości'],
            ['name' => 'Dział Sprzedaży', 'value' => 'sales_department', 'description' => 'Dział sprzedaży i marketingu'],
            ['name' => 'Dział Operacyjny', 'value' => 'operations_department', 'description' => 'Dział operacji i logistyki'],
        ];

        foreach ($departments as $index => $departmentData) {
            $department = new Dictionary();
            $department->setType('employee_departments')
                ->setName($departmentData['name'])
                ->setValue($departmentData['value'])
                ->setDescription($departmentData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor('#6f42c1')
                ->setIcon('ri-team-line');
            $manager->persist($department);
        }

        // Employee positions (stanowiska)
        $positions = [
            ['name' => 'Dyrektor', 'value' => 'director', 'description' => 'Dyrektor zarządzający'],
            ['name' => 'Kierownik', 'value' => 'manager', 'description' => 'Kierownik działu'],
            ['name' => 'Specjalista Senior', 'value' => 'senior_specialist', 'description' => 'Specjalista z doświadczeniem'],
            ['name' => 'Specjalista', 'value' => 'specialist', 'description' => 'Specjalista podstawowy'],
            ['name' => 'Młodszy Specjalista', 'value' => 'junior_specialist', 'description' => 'Początkujący specjalista'],
            ['name' => 'Asystent', 'value' => 'assistant', 'description' => 'Asystent biurowy'],
            ['name' => 'Stażysta', 'value' => 'intern', 'description' => 'Stażysta lub praktykant'],
        ];

        foreach ($positions as $index => $positionData) {
            $position = new Dictionary();
            $position->setType('employee_positions')
                ->setName($positionData['name'])
                ->setValue($positionData['value'])
                ->setDescription($positionData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor('#20c997')
                ->setIcon('ri-user-star-line');
            $manager->persist($position);
        }
    }

    private function updateUsersWithExampleData(ObjectManager $manager, User $adminUser, User $testUser, User $hrUser): void
    {
        // Ustaw przykładowe dane dla użytkowników
        $adminUser->setBranch('main_branch')
            ->setStatus('active')
            ->setSupervisor(null); // Admin nie ma przełożonego

        $hrUser->setBranch('main_branch')
            ->setStatus('active')
            ->setSupervisor($adminUser); // HR podlega adminowi

        $testUser->setBranch('warsaw_branch')
            ->setStatus('active')
            ->setSupervisor($hrUser); // Zwykły pracownik podlega HR

        $manager->persist($adminUser);
        $manager->persist($hrUser);
        $manager->persist($testUser);
    }

    private function createAsekuracijnyDictionaries(ObjectManager $manager): void
    {
        $dictionaryRepository = $manager->getRepository(Dictionary::class);
        
        // Check if asekuracyjny dictionaries already exist
        if ($dictionaryRepository->findOneBy(['type' => 'assek_equipment_types'])) {
            return; // Skip if dictionaries already exist
        }
        
        // Equipment types (typy sprzętu asekuracyjnego)
        $equipmentTypes = [
            ['name' => 'Szelki', 'value' => 'harness', 'description' => 'Szelki asekuracyjne', 'color' => '#28a745'],
            ['name' => 'Liny', 'value' => 'rope', 'description' => 'Liny asekuracyjne', 'color' => '#007bff'],
            ['name' => 'Kaski', 'value' => 'helmet', 'description' => 'Kaski ochronne', 'color' => '#ffc107'],
            ['name' => 'Zaciski', 'value' => 'ascender', 'description' => 'Zaciski i żumary', 'color' => '#6c757d'],
            ['name' => 'Blokady', 'value' => 'stopper', 'description' => 'Blokady i zabezpieczenia', 'color' => '#dc3545'],
        ];

        foreach ($equipmentTypes as $index => $typeData) {
            $type = new Dictionary();
            $type->setType('assek_equipment_types')
                ->setName($typeData['name'])
                ->setValue($typeData['value'])
                ->setDescription($typeData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor($typeData['color'])
                ->setIcon('ri-shield-line');
            $manager->persist($type);
        }

        // Review status (statusy przeglądów)
        $reviewStatuses = [
            ['name' => 'Przygotowanie', 'value' => 'preparation', 'description' => 'Przygotowanie do przeglądu', 'color' => '#6c757d'],
            ['name' => 'Na przeglądzie', 'value' => 'sent', 'description' => 'Wysłane na przegląd', 'color' => '#ffc107'],
            ['name' => 'Zakończone', 'value' => 'completed', 'description' => 'Przegląd zakończony', 'color' => '#28a745'],
            ['name' => 'Anulowane', 'value' => 'cancelled', 'description' => 'Przegląd anulowany', 'color' => '#dc3545'],
        ];

        foreach ($reviewStatuses as $index => $statusData) {
            $status = new Dictionary();
            $status->setType('assek_review_status')
                ->setName($statusData['name'])
                ->setValue($statusData['value'])
                ->setDescription($statusData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor($statusData['color'])
                ->setIcon('ri-check-double-line');
            $manager->persist($status);
        }

        // Set types (typy zestawów)
        $setTypes = [
            ['name' => 'Podstawowy', 'value' => 'basic', 'description' => 'Podstawowy zestaw asekuracyjny', 'color' => '#28a745'],
            ['name' => 'Zaawansowany', 'value' => 'advanced', 'description' => 'Zaawansowany zestaw asekuracyjny', 'color' => '#007bff'],
            ['name' => 'Specjalistyczny', 'value' => 'specialist', 'description' => 'Specjalistyczny zestaw asekuracyjny', 'color' => '#6f42c1'],
            ['name' => 'Ratowniczy', 'value' => 'rescue', 'description' => 'Zestaw do działań ratowniczych', 'color' => '#dc3545'],
        ];

        foreach ($setTypes as $index => $setData) {
            $setType = new Dictionary();
            $setType->setType('assek_set_types')
                ->setName($setData['name'])
                ->setValue($setData['value'])
                ->setDescription($setData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor($setData['color'])
                ->setIcon('ri-shield-star-line');
            $manager->persist($setType);
        }

        // Review types (typy przeglądów)
        $reviewTypes = [
            ['name' => 'Okresowy', 'value' => 'periodic', 'description' => 'Standardowy przegląd okresowy', 'color' => '#007bff'],
            ['name' => 'Kontrola po uszkodzeniu', 'value' => 'damage_control', 'description' => 'Przegląd po wykryciu uszkodzenia', 'color' => '#ffc107'],
            ['name' => 'Po naprawie', 'value' => 'post_repair', 'description' => 'Kontrola po wykonanej naprawie', 'color' => '#28a745'],
            ['name' => 'Początkowy', 'value' => 'initial', 'description' => 'Pierwszy przegląd nowego sprzętu', 'color' => '#6c757d'],
        ];

        foreach ($reviewTypes as $index => $reviewData) {
            $reviewType = new Dictionary();
            $reviewType->setType('assek_review_types')
                ->setName($reviewData['name'])
                ->setValue($reviewData['value'])
                ->setDescription($reviewData['description'])
                ->setIsActive(true)
                ->setIsSystem(true)
                ->setSortOrder($index + 1)
                ->setColor($reviewData['color'])
                ->setIcon('ri-calendar-check-line');
            $manager->persist($reviewType);
        }
    }

    private function createAsekuracijnyExampleData(ObjectManager $manager, User $createdBy): void
    {
        // Check if example equipment already exists
        $equipmentRepository = $manager->getRepository(AsekuracijnyEquipment::class);
        if ($equipmentRepository->findOneBy(['inventoryNumber' => 'ASK-001-2024'])) {
            return; // Skip if example data already exists
        }

        // Create example equipment
        $equipmentData = [
            [
                'inventory_number' => 'ASK-001-2024',
                'name' => 'Szelki robocze Petzl AVAO',
                'description' => 'Szelki całego ciała do prac na wysokości, z regulacją w 4 punktach',
                'equipment_type' => 'harness',
                'manufacturer' => 'Petzl',
                'model' => 'AVAO BOD',
                'serial_number' => 'C071BA001234',
                'manufacturing_date' => '2023-08-15',
                'purchase_date' => '2024-01-15',
                'purchase_price' => '450.00',
                'supplier' => 'TechClimb Sp. z o.o.',
                'invoice_number' => 'FV/2024/001',
                'warranty_expiry' => '2026-01-15',
                'next_review_date' => '2025-01-15',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn A, Szafa nr 1',
                'notes' => 'Nowy sprzęt, pierwszy przegląd po roku'
            ],
            [
                'inventory_number' => 'ASK-002-2024',
                'name' => 'Lina dynamiczna Edelrid Boa',
                'description' => 'Lina wspinaczkowa 9.8mm, 70m, certyfikat CE',
                'equipment_type' => 'rope',
                'manufacturer' => 'Edelrid',
                'model' => 'Boa 9.8mm',
                'serial_number' => 'ED2023B9870',
                'manufacturing_date' => '2023-05-20',
                'purchase_date' => '2024-02-10',
                'purchase_price' => '280.00',
                'supplier' => 'Alpine Sport',
                'invoice_number' => 'AS/2024/045',
                'warranty_expiry' => '2026-02-10',
                'next_review_date' => '2025-02-10',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn A, Szafa nr 2',
                'notes' => 'Regularnie sprawdzać na uszkodzenia'
            ],
            [
                'inventory_number' => 'ASK-003-2024',
                'name' => 'Kask Black Diamond Vector',
                'description' => 'Kask wspinaczkowy z regulacją, wentylowany',
                'equipment_type' => 'helmet',
                'manufacturer' => 'Black Diamond',
                'model' => 'Vector',
                'serial_number' => 'BD2023V5678',
                'manufacturing_date' => '2023-09-10',
                'purchase_date' => '2024-01-20',
                'purchase_price' => '180.00',
                'supplier' => 'Góralski Sklep',
                'invoice_number' => 'GS/2024/12',
                'warranty_expiry' => '2026-01-20',
                'next_review_date' => '2025-01-20',
                'review_interval_months' => 12,
                'status' => 'available',
                'location' => 'Magazyn B, Półka górna',
                'notes' => 'Sprawdzać mocowania przed użyciem'
            ]
        ];

        $equipment = [];
        foreach ($equipmentData as $data) {
            $eq = new AsekuracijnyEquipment();
            $eq->setInventoryNumber($data['inventory_number'])
               ->setName($data['name'])
               ->setDescription($data['description'])
               ->setEquipmentType($data['equipment_type'])
               ->setManufacturer($data['manufacturer'])
               ->setModel($data['model'])
               ->setSerialNumber($data['serial_number'])
               ->setManufacturingDate(new \DateTime($data['manufacturing_date']))
               ->setPurchaseDate(new \DateTime($data['purchase_date']))
               ->setPurchasePrice($data['purchase_price'])
               ->setSupplier($data['supplier'])
               ->setInvoiceNumber($data['invoice_number'])
               ->setWarrantyExpiry(new \DateTime($data['warranty_expiry']))
               ->setNextReviewDate(new \DateTime($data['next_review_date']))
               ->setReviewIntervalMonths($data['review_interval_months'])
               ->setStatus($data['status'])
               ->setLocation($data['location'])
               ->setNotes($data['notes'])
               ->setCreatedBy($createdBy);
            
            $manager->persist($eq);
            $equipment[] = $eq;
        }

        // Create example equipment set
        $equipmentSet = new AsekuracijnyEquipmentSet();
        $equipmentSet->setName('Zestaw podstawowy do prac na wysokości')
            ->setDescription('Kompletny zestaw do bezpiecznych prac na wysokości')
            ->setSetType('basic')
            ->setLocation('Magazyn A')
            ->setStatus('available')
            ->setCreatedBy($createdBy);
        
        $manager->persist($equipmentSet);

        // Add equipment to set
        foreach ($equipment as $index => $eq) {
            $setItem = new AsekuracijnyEquipmentSetItem();
            $setItem->setEquipmentSet($equipmentSet)
                ->setEquipment($eq)
                ->setQuantity(1)
                ->setSortOrder($index + 1);
            
            $manager->persist($setItem);
        }
    }
}
