<?php

namespace App\Controller;

use App\Entity\Module;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\EquipmentCategory;
use App\Service\SampleDataService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[Route('/install')]
class InstallerController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private SampleDataService $sampleDataService,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'installer_welcome')]
    public function welcome(Request $request): Response
    {
        $this->logger->info('Installer welcome page accessed', [
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ]);

        // Check if already installed
        if ($this->isInstalled()) {
            $this->logger->warning('Installer accessed but system already installed', [
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('home');
        }

        return $this->render('installer/welcome.html.twig');
    }

    #[Route('/requirements', name: 'installer_requirements')]
    public function requirements(Request $request): Response
    {
        if ($this->isInstalled()) {
            $this->logger->warning('Installer requirements accessed but system already installed', [
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('home');
        }

        $requirements = $this->checkRequirements();
        $canProceed = !in_array(false, array_column($requirements, 'status'));

        $this->logger->info('Installer requirements checked', [
            'ip' => $request->getClientIp(),
            'can_proceed' => $canProceed,
            'failed_requirements' => array_keys(array_filter($requirements, fn($req) => !$req['status']))
        ]);

        return $this->render('installer/requirements.html.twig', [
            'requirements' => $requirements,
            'can_proceed' => $canProceed
        ]);
    }

    #[Route('/database', name: 'installer_database')]
    public function database(Request $request): Response
    {
        if ($this->isInstalled()) {
            $this->logger->warning('Installer database accessed but system already installed', [
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $loadSampleData = $request->request->get('load_sample_data') === '1';
            
            $this->logger->info('Database installation started', [
                'ip' => $request->getClientIp(),
                'load_sample_data' => $loadSampleData
            ]);
            
            try {
                // Create database schema
                $this->createDatabaseSchema();
                $this->logger->info('Database schema created successfully', [
                    'ip' => $request->getClientIp()
                ]);
                
                // Insert basic data
                $this->insertBasicData();
                $this->logger->info('Basic data inserted successfully', [
                    'ip' => $request->getClientIp()
                ]);
                
                // Load sample data if requested
                if ($loadSampleData) {
                    $this->sampleDataService->loadSampleData();
                    $this->logger->info('Sample data loaded successfully', [
                        'ip' => $request->getClientIp()
                    ]);
                    $this->addFlash('success', 'Dane przykładowe zostały załadowane pomyślnie.');
                }
                
                return $this->redirectToRoute('installer_admin');
            } catch (\Exception $e) {
                $this->logger->error('Database installation failed', [
                    'ip' => $request->getClientIp(),
                    'error' => $e->getMessage(),
                    'load_sample_data' => $loadSampleData
                ]);
                $this->addFlash('error', 'Błąd podczas tworzenia bazy danych: ' . $e->getMessage());
            }
        }

        $this->logger->info('Installer database page accessed', [
            'ip' => $request->getClientIp()
        ]);

        return $this->render('installer/database.html.twig');
    }

    #[Route('/admin', name: 'installer_admin')]
    public function admin(Request $request): Response
    {
        if ($this->isInstalled()) {
            $this->logger->warning('Installer admin accessed but system already installed', [
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('home');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            
            $this->logger->info('Admin user creation started', [
                'ip' => $request->getClientIp(),
                'username' => $data['username'] ?? 'unknown',
                'email' => $data['email'] ?? 'unknown'
            ]);
            
            try {
                // Create admin user
                $this->createAdminUser($data);
                $this->logger->info('Admin user created successfully', [
                    'ip' => $request->getClientIp(),
                    'username' => $data['username'] ?? 'unknown'
                ]);
                
                // Mark as installed
                $this->markAsInstalled();
                $this->logger->info('System installation completed', [
                    'ip' => $request->getClientIp()
                ]);
                
                return $this->redirectToRoute('installer_finish');
            } catch (\Exception $e) {
                $this->logger->error('Admin user creation failed', [
                    'ip' => $request->getClientIp(),
                    'username' => $data['username'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                $this->addFlash('error', 'Błąd podczas tworzenia administratora: ' . $e->getMessage());
            }
        }

        $this->logger->info('Installer admin page accessed', [
            'ip' => $request->getClientIp()
        ]);

        return $this->render('installer/admin.html.twig');
    }

    #[Route('/finish', name: 'installer_finish')]
    public function finish(Request $request): Response
    {
        if (!$this->isInstalled()) {
            $this->logger->warning('Installer finish accessed but system not installed', [
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('installer_welcome');
        }

        $this->logger->info('Installation finished page accessed', [
            'ip' => $request->getClientIp()
        ]);

        return $this->render('installer/finish.html.twig');
    }

    private function isInstalled(): bool
    {
        $installFile = $this->getParameter('kernel.project_dir') . '/.installed';
        return file_exists($installFile);
    }

    private function markAsInstalled(): void
    {
        $installFile = $this->getParameter('kernel.project_dir') . '/.installed';
        $filesystem = new Filesystem();
        $filesystem->touch($installFile);
        file_put_contents($installFile, date('Y-m-d H:i:s'));
    }

    private function checkRequirements(): array
    {
        return [
            [
                'name' => 'PHP Version >= 8.2',
                'status' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
                'required' => '8.2.0'
            ],
            [
                'name' => 'MySQL Extension',
                'status' => extension_loaded('mysqli') || extension_loaded('pdo_mysql'),
                'current' => (extension_loaded('mysqli') || extension_loaded('pdo_mysql')) ? 'Zainstalowane' : 'Brak',
                'required' => 'Wymagane'
            ],
            [
                'name' => 'PDO Extension',
                'status' => extension_loaded('pdo'),
                'current' => extension_loaded('pdo') ? 'Zainstalowane' : 'Brak',
                'required' => 'Wymagane'
            ],
            [
                'name' => 'Intl Extension',
                'status' => extension_loaded('intl'),
                'current' => extension_loaded('intl') ? 'Zainstalowane' : 'Brak',
                'required' => 'Wymagane'
            ],
            [
                'name' => 'Writable var/ directory',
                'status' => is_writable($this->getParameter('kernel.project_dir') . '/var'),
                'current' => is_writable($this->getParameter('kernel.project_dir') . '/var') ? 'Zapisywalny' : 'Brak uprawnień',
                'required' => 'Zapisywalny'
            ]
        ];
    }

    private function createDatabaseSchema(): void
    {
        $this->logger->info('Starting database schema creation');
        
        try {
            $projectDir = $this->getParameter('kernel.project_dir');
            
            // First, drop all existing tables to start fresh
            $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
            $existingTables = $schemaManager->listTableNames();
            
            if (!empty($existingTables)) {
                $connection = $this->entityManager->getConnection();
                
                // Disable foreign key checks
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
                
                // Drop all tables except migration versions
                foreach ($existingTables as $table) {
                    if ($table !== 'doctrine_migration_versions') {
                        $connection->executeStatement('DROP TABLE IF EXISTS `' . $table . '`');
                    }
                }
                
                // Re-enable foreign key checks
                $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                
                // Clean migration status
                $this->entityManager->getConnection()->executeStatement('DELETE FROM doctrine_migration_versions');
            }
            
            // Run database migrations
            $process = new Process(['php', 'bin/console', 'doctrine:migrations:migrate', '--no-interaction'], $projectDir);
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new \Exception('Błąd podczas wykonywania migracji: ' . $process->getErrorOutput() . ' Output: ' . $process->getOutput());
            }
            
            // Verify tables were created
            $tables = $schemaManager->listTableNames();
            
            $requiredTables = ['modules', 'users', 'roles', 'user_roles', 'equipment_categories', 'equipment', 'equipment_log', 'equipment_attachment'];
            $missingTables = array_diff($requiredTables, $tables);
            
            if (!empty($missingTables)) {
                $this->logger->error('Database schema creation incomplete - missing tables', [
                    'missing_tables' => $missingTables,
                    'created_tables' => $tables
                ]);
                throw new \Exception('Brakujące tabele w bazie danych: ' . implode(', ', $missingTables) . '. Utworzone tabele: ' . implode(', ', $tables));
            }
            
            $this->logger->info('Database schema created successfully', [
                'created_tables' => $tables
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Database schema creation failed', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Błąd podczas tworzenia schematu bazy danych: ' . $e->getMessage());
        }
    }

    private function insertBasicData(): void
    {
        $this->logger->info('Starting basic data insertion');
        // Create modules
        $modules = [
            ['name' => 'admin', 'display_name' => 'Panel Administracyjny', 'description' => 'Zarządzanie systemem', 'is_enabled' => true],
            ['name' => 'equipment', 'display_name' => 'Sprzęt', 'description' => 'Zarządzanie sprzętem i narzędziami', 'is_enabled' => true],
            ['name' => 'asekuracja', 'display_name' => 'Asekuracja', 'description' => 'Zarządzanie sprzętem asekuracyjnym/wysokościowym', 'is_enabled' => true],
            ['name' => 'safety', 'display_name' => 'Sprzęt Ochronny', 'description' => 'Zarządzanie środkami ochrony osobistej', 'is_enabled' => false],
            ['name' => 'it', 'display_name' => 'Sprzęt IT', 'description' => 'Zarządzanie sprzętem informatycznym', 'is_enabled' => false],
            ['name' => 'vehicles', 'display_name' => 'Flota Pojazdów', 'description' => 'Zarządzanie flotą pojazdów', 'is_enabled' => false],
        ];

        foreach ($modules as $moduleData) {
            $existingModule = $this->entityManager->getRepository(Module::class)->findOneBy(['name' => $moduleData['name']]);
            if (!$existingModule) {
                $module = new Module();
                $module->setName($moduleData['name']);
                $module->setDisplayName($moduleData['display_name']);
                $module->setDescription($moduleData['description']);
                $module->setIsEnabled($moduleData['is_enabled']);
                $this->entityManager->persist($module);
            }
        }

        $this->entityManager->flush();

        // Create basic roles
        $adminModule = $this->entityManager->getRepository(Module::class)->findOneBy(['name' => 'admin']);
        $equipmentModule = $this->entityManager->getRepository(Module::class)->findOneBy(['name' => 'equipment']);
        $asekuracijaModule = $this->entityManager->getRepository(Module::class)->findOneBy(['name' => 'asekuracja']);

        $roles = [
            [
                'name' => 'system_admin',
                'description' => 'Pełny dostęp do panelu administracyjnego',
                'module' => $adminModule,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'CONFIGURE', 'EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC', 'EMPLOYEES_EDIT_FULL'],
                'is_system' => true
            ],
            [
                'name' => 'equipment_manager',
                'description' => 'Menedżer sprzętu - pełny dostęp',
                'module' => $equipmentModule,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'EXPORT'],
                'is_system' => true
            ],
            [
                'name' => 'equipment_viewer',
                'description' => 'Użytkownik sprzętu - tylko podgląd',
                'module' => $equipmentModule,
                'permissions' => ['VIEW'],
                'is_system' => true
            ],
            [
                'name' => 'employees_viewer',
                'description' => 'Przeglądanie listy pracowników',
                'module' => $adminModule,
                'permissions' => ['EMPLOYEES_VIEW'],
                'is_system' => true
            ],
            [
                'name' => 'employees_editor',
                'description' => 'Edycja podstawowych danych pracowników',
                'module' => $adminModule,
                'permissions' => ['EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC'],
                'is_system' => true
            ],
            [
                'name' => 'employees_manager',
                'description' => 'Pełne zarządzanie pracownikami',
                'module' => $adminModule,
                'permissions' => ['EMPLOYEES_VIEW', 'EMPLOYEES_EDIT_BASIC', 'EMPLOYEES_EDIT_FULL'],
                'is_system' => true
            ],
            // Asekuracja roles
            [
                'name' => 'ASSEK_ADMIN',
                'description' => 'Administrator Asekuracji - pełne prawa do modułu',
                'module' => $asekuracijaModule,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'DELETE', 'ASSIGN', 'REVIEW', 'TRANSFER'],
                'is_system' => false
            ],
            [
                'name' => 'ASSEK_EDITOR',
                'description' => 'Edytor Asekuracji - bez uprawnień do usuwania',
                'module' => $asekuracijaModule,
                'permissions' => ['VIEW', 'CREATE', 'EDIT', 'ASSIGN', 'REVIEW', 'TRANSFER'],
                'is_system' => false
            ],
            [
                'name' => 'ASSEK_VIEWER',
                'description' => 'Przeglądający Asekuracji - tylko podgląd',
                'module' => $asekuracijaModule,
                'permissions' => ['VIEW'],
                'is_system' => false
            ],
            [
                'name' => 'ASSEK_LIST',
                'description' => 'Lista Asekuracji - tylko lista zestawów i elementów',
                'module' => $asekuracijaModule,
                'permissions' => ['VIEW_LIST'],
                'is_system' => false
            ]
        ];

        foreach ($roles as $roleData) {
            $existingRole = $this->entityManager->getRepository(Role::class)->findOneBy([
                'name' => $roleData['name'],
                'module' => $roleData['module']
            ]);
            if (!$existingRole) {
                $role = new Role();
                $role->setName($roleData['name']);
                $role->setDescription($roleData['description']);
                $role->setModule($roleData['module']);
                $role->setPermissions($roleData['permissions']);
                $role->setIsSystemRole($roleData['is_system']);
                $this->entityManager->persist($role);
            }
        }

        // Create basic equipment categories
        $categories = [
            ['name' => 'Narzędzia ręczne', 'description' => 'Młotki, śrubokręty, klucze', 'color' => '#007bff', 'icon' => 'ri-hammer-line'],
            ['name' => 'Narzędzia elektryczne', 'description' => 'Wiertarki, szlifierki, piły', 'color' => '#28a745', 'icon' => 'ri-flashlight-line'],
            ['name' => 'Sprzęt pomiarowy', 'description' => 'Mierniki, poziomice, dalmierze', 'color' => '#ffc107', 'icon' => 'ri-ruler-line'],
            ['name' => 'Sprzęt ochronny', 'description' => 'Kaski, rękawice, okulary', 'color' => '#dc3545', 'icon' => 'ri-shield-line'],
        ];

        foreach ($categories as $i => $categoryData) {
            $existingCategory = $this->entityManager->getRepository(EquipmentCategory::class)->findOneBy(['name' => $categoryData['name']]);
            if (!$existingCategory) {
                $category = new EquipmentCategory();
                $category->setName($categoryData['name']);
                $category->setDescription($categoryData['description']);
                $category->setColor($categoryData['color']);
                $category->setIcon($categoryData['icon']);
                $category->setSortOrder($i + 1);
                $category->setIsActive(true);
                $category->setCreatedAt(new \DateTime());
                $category->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($category);
            }
        }

        $this->entityManager->flush();
        
        $this->logger->info('Basic data insertion completed successfully');
    }

    private function createAdminUser(array $data): void
    {
        $this->logger->info('Creating admin user', [
            'username' => $data['username'] ?? 'unknown'
        ]);
        
        // Check if admin user already exists
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            $this->logger->error('Admin user creation failed - user already exists', [
                'username' => $data['username']
            ]);
            throw new \Exception('Użytkownik o tej nazwie już istnieje');
        }

        // Create admin user
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setIsActive(true);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Assign admin role
        $adminRole = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'system_admin']);
        if ($adminRole) {
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($adminRole);
            $userRole->setAssignedBy($user);
            $userRole->setIsActive(true);
            $this->entityManager->persist($userRole);
        }

        // Assign asekuracja admin role
        $assekAdminRole = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => 'ASSEK_ADMIN']);
        if ($assekAdminRole) {
            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($assekAdminRole);
            $userRole->setAssignedBy($user);
            $userRole->setIsActive(true);
            $this->entityManager->persist($userRole);
        }

        $this->entityManager->flush();
        
        $this->logger->info('Admin user created and assigned admin role successfully', [
            'username' => $data['username']
        ]);
        
        // Create test users
        $this->createTestUsers();
    }

    private function createTestUsers(): void
    {
        $this->logger->info('Creating test users');
        
        // Test users data
        $testUsers = [
            [
                'username' => 'hr',
                'email' => 'hr@assethub.local',
                'firstName' => 'Anna',
                'lastName' => 'Nowak',
                'employeeNumber' => 'EMP002',
                'position' => 'Specjalista ds. kadr',
                'department' => 'HR',
                'password' => 'hr123',
                'role' => 'employees_editor'
            ],
            [
                'username' => 'user',
                'email' => 'user@assethub.local',
                'firstName' => 'Jan',
                'lastName' => 'Kowalski',
                'employeeNumber' => 'EMP001',
                'position' => 'Pracownik',
                'department' => 'Produkcja',
                'password' => 'user123',
                'role' => 'equipment_viewer'
            ]
        ];

        foreach ($testUsers as $userData) {
            // Check if user already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $userData['username']]);
            if ($existingUser) {
                $this->logger->info('Test user already exists, skipping', [
                    'username' => $userData['username']
                ]);
                continue;
            }

            // Create test user
            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmployeeNumber($userData['employeeNumber']);
            $user->setPosition($userData['position']);
            $user->setDepartment($userData['department']);
            $user->setIsActive(true);

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Assign role
            $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $userData['role']]);
            if ($role) {
                $userRole = new UserRole();
                $userRole->setUser($user);
                $userRole->setRole($role);
                $userRole->setAssignedBy($user); // Self-assigned for test users
                $userRole->setIsActive(true);
                $this->entityManager->persist($userRole);
            }

            $this->logger->info('Test user created successfully', [
                'username' => $userData['username'],
                'role' => $userData['role']
            ]);
        }

        $this->entityManager->flush();
        
        $this->logger->info('All test users created successfully');
    }
}