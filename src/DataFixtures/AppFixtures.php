<?php

namespace App\DataFixtures;

use App\Entity\Dictionary;
use App\Entity\Module;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
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
}
