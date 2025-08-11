<?php

namespace App\Controller\Admin;

use App\Entity\Module;
use App\Form\EmailSettingsType;
use App\Form\GeneralSettingsType;
use App\Form\LdapSettingsType;
use App\Repository\ModuleRepository;
use App\Service\AuthorizationService;
use App\Service\AdminService;
use App\Service\AuditService;
use App\Service\SettingService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AdminService $adminService,
        private AuditService $auditService,
        private ModuleRepository $moduleRepository,
        private SettingService $settingService,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/', name: 'admin_dashboard')]
    public function dashboard(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'admin', $request);

        // Get dashboard data via AdminService
        $dashboardData = $this->adminService->getDashboardData();

        // Audit dashboard access
        $this->auditService->logUserAction($user, 'view_admin_dashboard', [], $request);

        return $this->render('admin/dashboard.html.twig', $dashboardData);
    }

    #[Route('/modules', name: 'admin_modules')]
    public function modules(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'admin', $request);

        $modules = $this->moduleRepository->findAll();
        
        // Audit modules page access
        $this->auditService->logUserAction($user, 'view_admin_modules', [
            'modules_count' => count($modules)
        ], $request);

        return $this->render('admin/modules/index.html.twig', [
            'modules' => $modules,
        ]);
    }

    #[Route('/dictionaries', name: 'admin_dictionaries')]
    public function dictionaries(): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin dictionaries access attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        $this->logger->info('Admin dictionaries page accessed', [
            'user' => $user->getUsername(),
            'ip' => $this->getClientIp()
        ]);

        return $this->render('admin/dictionaries/index.html.twig');
    }

    #[Route('/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin settings access attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        $this->logger->info('Admin settings page accessed', [
            'user' => $user->getUsername(),
            'ip' => $this->getClientIp()
        ]);

        return $this->render('admin/settings/index.html.twig');
    }

    #[Route('/settings/general', name: 'admin_settings_general')]
    public function generalSettings(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin general settings access attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        // Pobierz obecne ustawienia
        $currentSettings = $this->settingService->getGeneralSettings();
        
        $form = $this->createForm(GeneralSettingsType::class, $currentSettings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $logoFile = $form->get('company_logo')->getData();
            
            // Sprawdź czy użytkownik wpisał kolor w polu tekstowym
            $colorFromText = $form->get('primary_color_text')->getData();
            if ($colorFromText && preg_match('/^#[0-9A-Fa-f]{6}$/', $colorFromText)) {
                $data['primary_color'] = $colorFromText;
            }
            
            // Sprawdź kolory sidebar
            $sidebarBgFromText = $form->get('sidebar_bg_color_text')->getData();
            if ($sidebarBgFromText && preg_match('/^#[0-9A-Fa-f]{6}$/', $sidebarBgFromText)) {
                $data['sidebar_bg_color'] = $sidebarBgFromText;
            }
            
            $sidebarTextFromText = $form->get('sidebar_text_color_text')->getData();
            if ($sidebarTextFromText && preg_match('/^#[0-9A-Fa-f]{6}$/', $sidebarTextFromText)) {
                $data['sidebar_text_color'] = $sidebarTextFromText;
            }
            
            $sidebarActiveFromText = $form->get('sidebar_active_color_text')->getData();
            if ($sidebarActiveFromText && preg_match('/^#[0-9A-Fa-f]{6}$/', $sidebarActiveFromText)) {
                $data['sidebar_active_color'] = $sidebarActiveFromText;
            }

            try {
                $this->settingService->saveGeneralSettings($data, $logoFile);
                
                $this->addFlash('success', 'Ustawienia zostały zapisane pomyślnie!');
                
                $this->logger->info('General settings updated successfully', [
                    'user' => $user->getUsername(),
                    'ip' => $this->getClientIp(),
                    'settings' => $data
                ]);

                return $this->redirectToRoute('admin_settings_general');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił błąd podczas zapisywania ustawień: ' . $e->getMessage());
                
                $this->logger->error('Failed to save general settings', [
                    'user' => $user->getUsername(),
                    'error' => $e->getMessage(),
                    'ip' => $this->getClientIp()
                ]);
            }
        }

        $this->logger->info('Admin general settings page accessed', [
            'user' => $user->getUsername(),
            'ip' => $this->getClientIp()
        ]);

        return $this->render('admin/settings/general.html.twig', [
            'form' => $form->createView(),
            'current_settings' => $currentSettings,
        ]);
    }

    #[Route('/settings/general/reset', name: 'admin_settings_general_reset', methods: ['POST'])]
    public function resetGeneralSettings(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin general settings reset attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        // Verify CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reset_general_settings', $submittedToken)) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('admin_settings_general');
        }

        try {
            // Reset to default values
            $defaultSettings = [
                'app_name' => 'AssetHub',
                'primary_color' => '#405189',
                'sidebar_bg_color' => '#2a3042',
                'sidebar_text_color' => '#ffffff',
                'sidebar_active_color' => '#405189',
            ];

            // Remove company logo if exists (reset to default)
            $currentLogo = $this->settingService->get('company_logo');
            if ($currentLogo && $currentLogo !== '/assets/images/logo-dark.png') {
                $logoPath = $this->getParameter('kernel.project_dir') . '/public' . $currentLogo;
                if (file_exists($logoPath) && strpos($currentLogo, '/uploads/logos/') !== false) {
                    unlink($logoPath);
                }
                $defaultSettings['company_logo'] = '/assets/images/logo-dark.png';
            }

            // Save all default settings
            foreach ($defaultSettings as $key => $value) {
                if ($key === 'app_name') {
                    $this->settingService->set($key, $value, 'general', 'text', 'Nazwa aplikacji');
                } elseif ($key === 'company_logo') {
                    $this->settingService->set($key, $value, 'general', 'file', 'Logo firmy');
                } else {
                    $this->settingService->set($key, $value, 'general', 'color', match($key) {
                        'primary_color' => 'Główny kolor aplikacji',
                        'sidebar_bg_color' => 'Kolor tła menu',
                        'sidebar_text_color' => 'Kolor tekstu w menu',
                        'sidebar_active_color' => 'Kolor aktywnego elementu w menu',
                        default => 'Kolor'
                    });
                }
            }

            $this->addFlash('success', 'Ustawienia zostały przywrócone do wartości domyślnych!');
            
            $this->logger->info('General settings reset to defaults', [
                'user' => $user->getUsername(),
                'ip' => $this->getClientIp(),
                'defaults' => $defaultSettings
            ]);

        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas przywracania domyślnych ustawień: ' . $e->getMessage());
            
            $this->logger->error('Failed to reset general settings to defaults', [
                'user' => $user->getUsername(),
                'error' => $e->getMessage(),
                'ip' => $this->getClientIp()
            ]);
        }

        return $this->redirectToRoute('admin_settings_general');
    }

    #[Route('/settings/email', name: 'admin_settings_email')]
    public function emailSettings(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin email settings access attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        // Pobierz obecne ustawienia email
        $currentSettings = $this->getEmailSettings();
        
        $form = $this->createForm(EmailSettingsType::class, $currentSettings);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            
            // Sprawdź który przycisk został kliknięty
            $isTestEmail = $form->get('test')->isClicked();
            
            if ($isTestEmail) {
                // Wysyłanie emaila testowego
                $testEmail = $form->get('test_email')->getData();
                
                if (!$testEmail) {
                    $this->addFlash('error', 'Podaj adres email do wysłania wiadomości testowej');
                } else {
                    try {
                        $this->sendTestEmail($testEmail, $data);
                        $this->addFlash('success', 'Wiadomość testowa została wysłana pomyślnie na adres: ' . $testEmail);
                        
                        $this->logger->info('Test email sent successfully', [
                            'user' => $user->getUsername(),
                            'test_email' => $testEmail,
                            'ip' => $this->getClientIp()
                        ]);
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Błąd podczas wysyłania wiadomości testowej: ' . $e->getMessage());
                        
                        $this->logger->error('Failed to send test email', [
                            'user' => $user->getUsername(),
                            'test_email' => $testEmail,
                            'error' => $e->getMessage(),
                            'ip' => $this->getClientIp()
                        ]);
                    }
                }
            } else if ($form->isValid()) {
                // Zapisywanie ustawień
                try {
                    $this->saveEmailSettings($data);
                    
                    $this->addFlash('success', 'Ustawienia email zostały zapisane pomyślnie!');
                    
                    $this->logger->info('Email settings updated successfully', [
                        'user' => $user->getUsername(),
                        'ip' => $this->getClientIp()
                    ]);

                    return $this->redirectToRoute('admin_settings_email');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas zapisywania ustawień: ' . $e->getMessage());
                    
                    $this->logger->error('Failed to save email settings', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            }
        }

        $this->logger->info('Admin email settings page accessed', [
            'user' => $user->getUsername(),
            'ip' => $this->getClientIp()
        ]);

        return $this->render('admin/settings/email.html.twig', [
            'form' => $form->createView(),
            'current_settings' => $currentSettings,
        ]);
    }

    #[Route('/settings/database', name: 'admin_settings_database')]
    public function databaseSettings(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'admin', $request);

        // Get database info via AdminService
        $databaseInfo = $this->adminService->getDatabaseInfo();
        
        // Handle POST operations
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            
            try {
                switch ($action) {
                    case 'backup':
                        $filename = $this->adminService->createDatabaseBackup();
                        $this->addFlash('success', 'Kopia zapasowa została utworzona: ' . $filename);
                        
                        $this->auditService->logUserAction($user, 'database_backup_created', [
                            'filename' => $filename
                        ], $request);
                        break;
                        
                    case 'optimize':
                        $result = $this->adminService->performDatabaseMaintenance('optimize');
                        $this->addFlash('success', 'Baza danych została zoptymalizowana. Zoptymalizowano ' . $result['affected_tables'] . ' tabel.');
                        
                        $this->auditService->logUserAction($user, 'database_optimized', [
                            'optimized_tables' => $result['affected_tables']
                        ], $request);
                        break;
                        
                    case 'analyze':
                        $result = $this->adminService->performDatabaseMaintenance('analyze');
                        $this->addFlash('success', 'Analiza bazy danych została zakończona. Przeanalizowano ' . $result['affected_tables'] . ' tabel.');
                        
                        $this->auditService->logUserAction($user, 'database_analyzed', [
                            'analyzed_tables' => $result['affected_tables']
                        ], $request);
                        break;
                        
                    case 'clear_logs':
                        $result = $this->adminService->clearSystemLogs();
                        $this->addFlash('success', 'Stare logi zostały wyczyszczone. Usunięto ' . $result['deleted_files'] . ' plików.');
                        
                        $this->auditService->logUserAction($user, 'system_logs_cleared', [
                            'deleted_files' => $result['deleted_files']
                        ], $request);
                        break;
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Błąd podczas wykonywania operacji: ' . $e->getMessage());
                
                $this->auditService->logUserAction($user, 'database_operation_failed', [
                    'action' => $action,
                    'error' => $e->getMessage()
                ], $request);
            }
            
            return $this->redirectToRoute('admin_settings_database');
        }

        // Audit database settings page access
        $this->auditService->logUserAction($user, 'view_admin_database_settings', [], $request);

        return $this->render('admin/settings/database.html.twig', [
            'database_info' => $databaseInfo,
        ]);
    }

    #[Route('/settings/ldap', name: 'admin_settings_ldap')]
    public function ldapSettings(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            $this->logger->warning('Unauthorized admin LDAP settings access attempt', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $this->getClientIp()
            ]);
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        // Pobierz obecne ustawienia LDAP
        $currentSettings = $this->getLdapSettings();
        
        $form = $this->createForm(LdapSettingsType::class, $currentSettings);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();
            
            // Sprawdź który przycisk został kliknięty
            $isTestConnection = $form->get('test')->isClicked();
            $isSyncExisting = $form->get('sync_existing')->isClicked();
            $isSyncNew = $form->get('sync_new')->isClicked();
            $isSyncHierarchy = $form->get('sync_hierarchy')->isClicked();
            
            if ($isTestConnection) {
                // Testowanie połączenia LDAP
                try {
                    $result = $this->testLdapConnection($data);
                    $message = 'Połączenie LDAP działa prawidłowo! Znaleziono ' . $result['user_count'] . ' użytkowników.';
                    if (!empty($result['sample_users'])) {
                        $message .= ' Przykładowi użytkownicy: ';
                        $examples = [];
                        foreach ($result['sample_users'] as $sampleUser) {
                            $examples[] = $sampleUser['username'] . ' (' . $sampleUser['email'] . ')';
                        }
                        $message .= implode(', ', array_slice($examples, 0, 3));
                        if (count($examples) > 3) {
                            $message .= '...';
                        }
                    }
                    $this->addFlash('success', $message);
                    
                    $this->logger->info('LDAP connection test successful', [
                        'user' => $user->getUsername(),
                        'ldap_host' => $data['ldap_host'],
                        'user_count' => $result['user_count'],
                        'ip' => $this->getClientIp()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd połączenia LDAP: ' . $e->getMessage());
                    
                    $this->logger->error('LDAP connection test failed', [
                        'user' => $user->getUsername(),
                        'ldap_host' => $data['ldap_host'],
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            } elseif ($isSyncExisting) {
                // Synchronizacja istniejących użytkowników
                try {
                    $result = $this->syncExistingUsers($data);
                    $message = 'Zsynchronizowano ' . $result['updated'] . ' istniejących użytkowników z LDAP.';
                    if ($result['errors'] > 0) {
                        $message .= ' Błędy: ' . $result['errors'] . ' użytkowników.';
                    }
                    $this->addFlash('success', $message);
                    
                    $this->logger->info('LDAP existing users sync completed', [
                        'user' => $user->getUsername(),
                        'updated_users' => $result['updated'],
                        'ip' => $this->getClientIp()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd synchronizacji: ' . $e->getMessage());
                    
                    $this->logger->error('LDAP existing users sync failed', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            } elseif ($isSyncNew) {
                // Synchronizacja nowych użytkowników
                try {
                    $result = $this->syncNewUsers($data);
                    $message = 'Dodano ' . $result['created'] . ' nowych użytkowników z LDAP.';
                    if ($result['errors'] > 0) {
                        $message .= ' Błędy: ' . $result['errors'] . ' użytkowników.';
                    }
                    $this->addFlash('success', $message);
                    
                    $this->logger->info('LDAP new users sync completed', [
                        'user' => $user->getUsername(),
                        'created_users' => $result['created'],
                        'ip' => $this->getClientIp()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd synchronizacji: ' . $e->getMessage());
                    
                    $this->logger->error('LDAP new users sync failed', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            } elseif ($isSyncHierarchy) {
                // Synchronizacja hierarchii przełożonych
                try {
                    $result = $this->syncManagerHierarchy($data);
                    $message = 'Zaktualizowano hierarchię dla ' . $result['updated'] . ' użytkowników.';
                    if ($result['errors'] > 0) {
                        $message .= ' Błędy: ' . $result['errors'] . ' użytkowników.';
                    }
                    $this->addFlash('success', $message);
                    
                    $this->logger->info('LDAP hierarchy sync completed', [
                        'user' => $user->getUsername(),
                        'updated_relationships' => $result['updated'],
                        'errors' => $result['errors'],
                        'ip' => $this->getClientIp()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Błąd synchronizacji hierarchii: ' . $e->getMessage());
                    
                    $this->logger->error('LDAP hierarchy sync failed', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            } elseif ($form->isValid()) {
                // Zapisywanie ustawień
                try {
                    $this->saveLdapSettings($data);
                    
                    $this->addFlash('success', 'Ustawienia LDAP zostały zapisane pomyślnie!');
                    
                    $this->logger->info('LDAP settings updated successfully', [
                        'user' => $user->getUsername(),
                        'ip' => $this->getClientIp()
                    ]);

                    return $this->redirectToRoute('admin_settings_ldap');
                    
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas zapisywania ustawień: ' . $e->getMessage());
                    
                    $this->logger->error('Failed to save LDAP settings', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'ip' => $this->getClientIp()
                    ]);
                }
            }
        }

        $this->logger->info('Admin LDAP settings page accessed', [
            'user' => $user->getUsername(),
            'ip' => $this->getClientIp()
        ]);

        return $this->render('admin/settings/ldap.html.twig', [
            'form' => $form->createView(),
            'current_settings' => $currentSettings,
        ]);
    }

    private function getEmailSettings(): array
    {
        return [
            'smtp_host' => $this->settingService->get('smtp_host', 'localhost'),
            'smtp_port' => (int) $this->settingService->get('smtp_port', '587'),
            'smtp_encryption' => $this->settingService->get('smtp_encryption', 'tls'),
            'smtp_username' => $this->settingService->get('smtp_username', ''),
            'smtp_password' => '', // Nigdy nie pobieraj hasła do formularza
            'from_email' => $this->settingService->get('from_email', 'noreply@localhost'),
            'from_name' => $this->settingService->get('from_name', 'AssetHub System'),
        ];
    }

    private function saveEmailSettings(array $data): void
    {
        $this->settingService->set('smtp_host', $data['smtp_host'], 'email', 'text', 'Serwer SMTP');
        $this->settingService->set('smtp_port', (string) $data['smtp_port'], 'email', 'number', 'Port SMTP');
        $this->settingService->set('smtp_encryption', $data['smtp_encryption'], 'email', 'text', 'Typ szyfrowania SMTP');
        $this->settingService->set('smtp_username', $data['smtp_username'], 'email', 'text', 'Nazwa użytkownika SMTP');
        $this->settingService->set('from_email', $data['from_email'], 'email', 'email', 'Adres nadawcy');
        $this->settingService->set('from_name', $data['from_name'], 'email', 'text', 'Nazwa nadawcy');
        
        // Zapisz hasło tylko jeśli zostało podane
        if (!empty($data['smtp_password'])) {
            $this->settingService->set('smtp_password', $data['smtp_password'], 'email', 'password', 'Hasło SMTP');
        }
    }

    private function sendTestEmail(string $testEmail, array $smtpSettings): void
    {
        // Pobierz hasło z bazy danych jeśli nie zostało podane w formularzu
        $password = $smtpSettings['smtp_password'];
        if (empty($password)) {
            $password = $this->settingService->get('smtp_password', '');
        }
        
        // Buduj DSN na podstawie ustawień SMTP
        $encryption = $smtpSettings['smtp_encryption'] !== 'none' ? $smtpSettings['smtp_encryption'] : null;
        $dsnParts = [
            'smtp://',
            urlencode($smtpSettings['smtp_username']),
            ':',
            urlencode($password),
            '@',
            $smtpSettings['smtp_host'],
            ':',
            $smtpSettings['smtp_port']
        ];
        
        if ($encryption) {
            $dsnParts[] = '?encryption=' . $encryption;
        }
        
        $dsn = implode('', $dsnParts);
        
        // Utwórz transport SMTP na podstawie konfiguracji
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);
        
        // Utwórz wiadomość
        $email = (new Email())
            ->from($smtpSettings['from_email'])
            ->to($testEmail)
            ->subject('Test wiadomości z AssetHub')
            ->html('
                <h2>Test połączenia SMTP</h2>
                <p>Gratulacje! Konfiguracja SMTP działa prawidłowo.</p>
                <p><strong>Szczegóły połączenia:</strong></p>
                <ul>
                    <li>Serwer: ' . htmlspecialchars($smtpSettings['smtp_host']) . '</li>
                    <li>Port: ' . htmlspecialchars($smtpSettings['smtp_port']) . '</li>
                    <li>Szyfrowanie: ' . htmlspecialchars($smtpSettings['smtp_encryption']) . '</li>
                    <li>Użytkownik: ' . htmlspecialchars($smtpSettings['smtp_username']) . '</li>
                </ul>
                <p><em>Wiadomość wysłana z systemu AssetHub</em></p>
            ');

        // Wyślij przez skonfigurowany transport
        $mailer->send($email);
    }

    private function getLdapSettings(): array
    {
        return [
            'ldap_enabled' => (bool) $this->settingService->get('ldap_enabled', false),
            'ldap_host' => $this->settingService->get('ldap_host', 'ldap://localhost'),
            'ldap_port' => (int) $this->settingService->get('ldap_port', '389'),
            'ldap_encryption' => $this->settingService->get('ldap_encryption', 'none'),
            'ldap_bind_dn' => $this->settingService->get('ldap_bind_dn', ''),
            'ldap_bind_password' => '', // Nigdy nie pobieraj hasła do formularza
            'ldap_base_dn' => $this->settingService->get('ldap_base_dn', ''),
            'ldap_user_filter' => $this->settingService->get('ldap_user_filter', '(&(objectClass=user)(!(userAccountControl:1.2.840.113556.1.4.803:=2)))'),
            'ldap_map_username' => $this->settingService->get('ldap_map_username', 'sAMAccountName'),
            'ldap_map_email' => $this->settingService->get('ldap_map_email', 'mail'),
            'ldap_map_firstname' => $this->settingService->get('ldap_map_firstname', 'givenName'),
            'ldap_map_lastname' => $this->settingService->get('ldap_map_lastname', 'sn'),
            'ldap_map_displayname' => $this->settingService->get('ldap_map_displayname', 'displayName'),
            
            // Mapowanie pól pracowniczych
            'ldap_map_employee_number' => $this->settingService->get('ldap_map_employee_number', 'employeeNumber'),
            'ldap_map_phone' => $this->settingService->get('ldap_map_phone', 'telephoneNumber'),
            'ldap_map_position' => $this->settingService->get('ldap_map_position', 'title'),
            'ldap_map_department' => $this->settingService->get('ldap_map_department', 'department'),
            'ldap_map_office' => $this->settingService->get('ldap_map_office', 'physicalDeliveryOfficeName'),
            'ldap_map_manager' => $this->settingService->get('ldap_map_manager', 'manager'),
            'ldap_map_status' => $this->settingService->get('ldap_map_status', 'userAccountControl'),
            
            'ldap_auto_create_users' => (bool) $this->settingService->get('ldap_auto_create_users', false),
            'ldap_update_existing_users' => (bool) $this->settingService->get('ldap_update_existing_users', false),
        ];
    }

    private function saveLdapSettings(array $data): void
    {
        $this->settingService->set('ldap_enabled', $data['ldap_enabled'] ? '1' : '0', 'ldap', 'boolean', 'Włącz integrację LDAP');
        $this->settingService->set('ldap_host', $data['ldap_host'], 'ldap', 'text', 'Serwer LDAP');
        $this->settingService->set('ldap_port', (string) $data['ldap_port'], 'ldap', 'number', 'Port LDAP');
        $this->settingService->set('ldap_encryption', $data['ldap_encryption'], 'ldap', 'text', 'Typ szyfrowania LDAP');
        $this->settingService->set('ldap_bind_dn', $data['ldap_bind_dn'], 'ldap', 'text', 'Bind DN użytkownika serwisowego');
        $this->settingService->set('ldap_base_dn', $data['ldap_base_dn'], 'ldap', 'text', 'Base DN katalog bazowy');
        $this->settingService->set('ldap_user_filter', $data['ldap_user_filter'], 'ldap', 'text', 'Filtr użytkowników LDAP');
        $this->settingService->set('ldap_map_username', $data['ldap_map_username'], 'ldap', 'text', 'Mapowanie pola username');
        $this->settingService->set('ldap_map_email', $data['ldap_map_email'], 'ldap', 'text', 'Mapowanie pola email');
        $this->settingService->set('ldap_map_firstname', $data['ldap_map_firstname'], 'ldap', 'text', 'Mapowanie pola imię');
        $this->settingService->set('ldap_map_lastname', $data['ldap_map_lastname'], 'ldap', 'text', 'Mapowanie pola nazwisko');
        $this->settingService->set('ldap_map_displayname', $data['ldap_map_displayname'], 'ldap', 'text', 'Mapowanie pola pełna nazwa');
        
        // Mapowanie pól pracowniczych
        $this->settingService->set('ldap_map_employee_number', $data['ldap_map_employee_number'] ?? '', 'ldap', 'text', 'Mapowanie pola numer pracownika');
        $this->settingService->set('ldap_map_phone', $data['ldap_map_phone'] ?? '', 'ldap', 'text', 'Mapowanie pola telefon');
        $this->settingService->set('ldap_map_position', $data['ldap_map_position'] ?? '', 'ldap', 'text', 'Mapowanie pola stanowisko');
        $this->settingService->set('ldap_map_department', $data['ldap_map_department'] ?? '', 'ldap', 'text', 'Mapowanie pola dział');
        $this->settingService->set('ldap_map_office', $data['ldap_map_office'] ?? '', 'ldap', 'text', 'Mapowanie pola oddział/biuro');
        $this->settingService->set('ldap_map_manager', $data['ldap_map_manager'] ?? '', 'ldap', 'text', 'Mapowanie pola przełożony');
        $this->settingService->set('ldap_map_status', $data['ldap_map_status'] ?? '', 'ldap', 'text', 'Mapowanie pola status');
        
        $this->settingService->set('ldap_auto_create_users', $data['ldap_auto_create_users'] ? '1' : '0', 'ldap', 'boolean', 'Automatycznie twórz nowych użytkowników');
        $this->settingService->set('ldap_update_existing_users', $data['ldap_update_existing_users'] ? '1' : '0', 'ldap', 'boolean', 'Aktualizuj istniejących użytkowników');
        
        // Zapisz hasło tylko jeśli zostało podane
        if (!empty($data['ldap_bind_password'])) {
            $this->settingService->set('ldap_bind_password', $data['ldap_bind_password'], 'ldap', 'password', 'Hasło użytkownika serwisowego LDAP');
        }
    }

    private function testLdapConnection(array $ldapSettings): array
    {
        // Walidacja podstawowych parametrów
        if (empty($ldapSettings['ldap_host'])) {
            throw new \Exception('Host LDAP jest wymagany');
        }
        
        if (empty($ldapSettings['ldap_port']) || !is_numeric($ldapSettings['ldap_port'])) {
            throw new \Exception('Port LDAP musi być liczbą');
        }
        
        if (empty($ldapSettings['ldap_bind_dn'])) {
            throw new \Exception('Bind DN jest wymagany');
        }
        
        if (empty($ldapSettings['ldap_base_dn'])) {
            throw new \Exception('Base DN jest wymagany');
        }
        
        if (empty($ldapSettings['ldap_user_filter'])) {
            throw new \Exception('Filtr użytkowników jest wymagany');
        }

        // Pobierz hasło z bazy danych jeśli nie zostało podane w formularzu
        $password = $ldapSettings['ldap_bind_password'];
        if (empty($password)) {
            $password = $this->settingService->get('ldap_bind_password', '');
        }
        
        if (empty($password)) {
            throw new \Exception('Hasło bind użytkownika jest wymagane');
        }

        // Utwórz połączenie LDAP
        $ldap = $this->createLdapConnection($ldapSettings, $password);
        
        // Testuj bind (uwierzytelnianie)
        try {
            $ldap->bind($ldapSettings['ldap_bind_dn'], $password);
        } catch (\Exception $e) {
            throw new \Exception('Błąd uwierzytelniania LDAP: ' . $e->getMessage());
        }
        
        // Testuj wyszukiwanie użytkowników
        try {
            $query = $ldap->query(
                $ldapSettings['ldap_base_dn'],
                $ldapSettings['ldap_user_filter'],
                [
                    'maxItems' => 1000,
                    'timeout' => 30
                ]
            );
            
            $results = $query->execute();
            $userCount = count($results);
            
            if ($userCount === 0) {
                throw new \Exception('Nie znaleziono użytkowników spełniających kryteria filtra');
            }
        } catch (\Exception $e) {
            throw new \Exception('Błąd wyszukiwania LDAP: ' . $e->getMessage());
        }
        
        // Pobierz przykładowych użytkowników dla podglądu
        $sampleUsers = [];
        $count = 0;
        foreach ($results as $entry) {
            if ($count >= 5) break; // Maksymalnie 5 przykładów
            
            $sampleUsers[] = [
                'dn' => $entry->getDn(),
                'username' => $this->getLdapAttribute($entry, $ldapSettings['ldap_map_username']),
                'email' => $this->getLdapAttribute($entry, $ldapSettings['ldap_map_email']),
                'name' => $this->getLdapAttribute($entry, $ldapSettings['ldap_map_displayname']) 
                    ?: $this->getLdapAttribute($entry, $ldapSettings['ldap_map_firstname']) . ' ' . $this->getLdapAttribute($entry, $ldapSettings['ldap_map_lastname'])
            ];
            $count++;
        }
        
        return [
            'user_count' => $userCount,
            'connection_successful' => true,
            'sample_users' => $sampleUsers
        ];
    }

    private function syncExistingUsers(array $ldapSettings): array
    {
        $password = $ldapSettings['ldap_bind_password'];
        if (empty($password)) {
            $password = $this->settingService->get('ldap_bind_password', '');
        }

        $ldap = $this->createLdapConnection($ldapSettings, $password);
        $ldap->bind($ldapSettings['ldap_bind_dn'], $password);
        
        // Pobierz wszystkich użytkowników z LDAP
        $query = $ldap->query(
            $ldapSettings['ldap_base_dn'],
            $ldapSettings['ldap_user_filter'],
            ['maxItems' => 5000, 'timeout' => 60]
        );
        
        $ldapResults = $query->execute();
        
        // Pobierz wszystkich istniejących użytkowników z bazy
        $existingUsers = $this->userRepository->findAll();
        $existingUsernames = [];
        foreach ($existingUsers as $user) {
            $existingUsernames[strtolower($user->getUsername())] = $user;
        }
        
        $updated = 0;
        $errors = 0;
        
        foreach ($ldapResults as $entry) {
            try {
                $username = strtolower($this->getLdapAttribute($entry, $ldapSettings['ldap_map_username']));
                
                if (isset($existingUsernames[$username])) {
                    $user = $existingUsernames[$username];
                    
                    // Aktualizuj dane użytkownika z LDAP
                    $email = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_email']);
                    $firstname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_firstname']);
                    $lastname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_lastname']);
                    $displayname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_displayname']);
                    
                    // Nowe pola pracownicze
                    $employeeNumber = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_employee_number'] ?? '');
                    $phone = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_phone'] ?? '');
                    $position = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_position'] ?? '');
                    $department = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_department'] ?? '');
                    $office = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_office'] ?? '');
                    $manager = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_manager'] ?? '');
                    $status = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_status'] ?? '');
                    
                    // Aktualizuj podstawowe pola
                    if ($email && $email !== $user->getEmail()) {
                        $user->setEmail($email);
                    }
                    
                    if ($firstname && $firstname !== $user->getFirstName()) {
                        $user->setFirstName($firstname);
                    }
                    
                    if ($lastname && $lastname !== $user->getLastName()) {
                        $user->setLastName($lastname);
                    }
                    
                    // Aktualizuj pola pracownicze
                    if ($employeeNumber && $employeeNumber !== $user->getEmployeeNumber()) {
                        $user->setEmployeeNumber($employeeNumber);
                    }
                    
                    if ($phone && $phone !== $user->getPhoneNumber()) {
                        $user->setPhoneNumber($phone);
                    }
                    
                    if ($position && $position !== $user->getPosition()) {
                        $user->setPosition($position);
                    }
                    
                    if ($department && $department !== $user->getDepartment()) {
                        $user->setDepartment($department);
                    }
                    
                    // Mapowanie lokalizacji na oddział (może wymagać logiki mapowania)
                    if ($office) {
                        $branchValue = $this->mapOfficeToBranch($office);
                        if ($branchValue && $branchValue !== $user->getBranch()) {
                            $user->setBranch($branchValue);
                        }
                    }
                    
                    // Mapowanie statusu LDAP na status pracownika
                    if ($status) {
                        $statusValue = $this->mapLdapStatusToEmployeeStatus($status);
                        if ($statusValue && $statusValue !== $user->getStatus()) {
                            $user->setStatus($statusValue);
                        }
                    }
                    
                    // Zapisz DN do późniejszej synchronizacji hierarchii
                    if ($user->getLdapDn() !== $entry->getDn()) {
                        $user->setLdapDn($entry->getDn());
                    }
                    
                    // Zapisz zmiany
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                    $updated++;
                    
                    $this->logger->info('Updated user from LDAP', [
                        'username' => $username,
                        'email' => $email
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Error updating user from LDAP', [
                    'username' => $username ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    private function syncNewUsers(array $ldapSettings): array
    {
        $password = $ldapSettings['ldap_bind_password'];
        if (empty($password)) {
            $password = $this->settingService->get('ldap_bind_password', '');
        }

        $ldap = $this->createLdapConnection($ldapSettings, $password);
        $ldap->bind($ldapSettings['ldap_bind_dn'], $password);
        
        // Pobierz wszystkich użytkowników z LDAP
        $query = $ldap->query(
            $ldapSettings['ldap_base_dn'],
            $ldapSettings['ldap_user_filter'],
            ['maxItems' => 5000, 'timeout' => 60]
        );
        
        $ldapResults = $query->execute();
        
        // Pobierz listę istniejących usernames z bazy
        $existingUsernames = [];
        $existingUsers = $this->userRepository->findAll();
        foreach ($existingUsers as $user) {
            $existingUsernames[] = strtolower($user->getUsername());
        }
        
        $created = 0;
        $errors = 0;
        
        foreach ($ldapResults as $entry) {
            try {
                $username = strtolower($this->getLdapAttribute($entry, $ldapSettings['ldap_map_username']));
                
                // Sprawdź czy użytkownik już istnieje
                if (!in_array($username, $existingUsernames)) {
                    $email = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_email']);
                    $firstname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_firstname']);
                    $lastname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_lastname']);
                    $displayname = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_displayname']);
                    
                    // Nowe pola pracownicze
                    $employeeNumber = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_employee_number'] ?? '');
                    $phone = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_phone'] ?? '');
                    $position = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_position'] ?? '');
                    $department = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_department'] ?? '');
                    $office = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_office'] ?? '');
                    $manager = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_manager'] ?? '');
                    $status = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_status'] ?? '');
                    
                    // Walidacja wymaganych pól
                    if (empty($username) || empty($email)) {
                        $errors++;
                        continue;
                    }
                    
                    // Utwórz nowego użytkownika
                    $newUser = new \App\Entity\User();
                    $newUser->setUsername($username);
                    $newUser->setEmail($email);
                    $newUser->setIsActive(true);
                    $newUser->setCreatedAt(new \DateTime());
                    
                    // Ustaw podstawowe pola
                    if ($firstname) {
                        $newUser->setFirstName($firstname);
                    }
                    if ($lastname) {
                        $newUser->setLastName($lastname);
                    }
                    
                    // Ustaw pola pracownicze
                    if ($employeeNumber) {
                        $newUser->setEmployeeNumber($employeeNumber);
                    }
                    if ($phone) {
                        $newUser->setPhoneNumber($phone);
                    }
                    if ($position) {
                        $newUser->setPosition($position);
                    }
                    if ($department) {
                        $newUser->setDepartment($department);
                    }
                    
                    // Mapowanie lokalizacji na oddział
                    if ($office) {
                        $branchValue = $this->mapOfficeToBranch($office);
                        if ($branchValue) {
                            $newUser->setBranch($branchValue);
                        }
                    }
                    
                    // Mapowanie statusu LDAP na status pracownika
                    if ($status) {
                        $statusValue = $this->mapLdapStatusToEmployeeStatus($status);
                        if ($statusValue) {
                            $newUser->setStatus($statusValue);
                        }
                    } else {
                        // Domyślny status dla nowych użytkowników
                        $newUser->setStatus('active');
                    }
                    
                    // Zapisz DN dla hierarchii przełożonych
                    $newUser->setLdapDn($entry->getDn());
                    
                    // Ustaw domyślne hasło (użytkownik będzie logował się przez LDAP)
                    $newUser->setPassword(password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT));
                    
                    $this->entityManager->persist($newUser);
                    $this->entityManager->flush();
                    $created++;
                    
                    $this->logger->info('Created new user from LDAP', [
                        'username' => $username,
                        'email' => $email
                    ]);
                }
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Error creating user from LDAP', [
                    'username' => $username ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'created' => $created,
            'errors' => $errors
        ];
    }

    private function createLdapConnection(array $ldapSettings, string $password): LdapInterface
    {
        // Przygotuj host - usuń prefiks protokołu jeśli istnieje
        $host = $ldapSettings['ldap_host'];
        $host = preg_replace('/^(ldaps?:\/\/)/', '', $host);
        
        // Przygotuj opcje połączenia
        $options = [
            'host' => $host,
            'port' => intval($ldapSettings['ldap_port']),
        ];
        
        // Dodaj szyfrowanie jeśli skonfigurowane
        if (isset($ldapSettings['ldap_encryption'])) {
            if ($ldapSettings['ldap_encryption'] === 'ssl') {
                $options['encryption'] = 'ssl';
            } elseif ($ldapSettings['ldap_encryption'] === 'starttls') {
                $options['encryption'] = 'tls';
            }
        }
        
        // Dodaj dodatkowe opcje dla lepszej kompatybilności
        $options['version'] = 3;
        $options['referrals'] = false;
        
        try {
            // Sprawdź dostępne adaptery
            $this->logger->info('Creating LDAP connection', [
                'host' => $host,
                'port' => $options['port'],
                'encryption' => $ldapSettings['ldap_encryption'] ?? 'none',
                'options' => $options
            ]);
            
            // Sprawdź czy rozszerzenie LDAP jest zainstalowane
            if (!extension_loaded('ldap')) {
                throw new \Exception('Rozszerzenie PHP LDAP nie jest zainstalowane');
            }
            
            return Ldap::create('ext_ldap', $options);
        } catch (\Exception $e) {
            $this->logger->error('Failed to create LDAP connection', [
                'error' => $e->getMessage(),
                'host' => $host,
                'port' => $options['port'],
                'encryption' => $ldapSettings['ldap_encryption'] ?? 'none',
                'ldap_extension_loaded' => extension_loaded('ldap'),
                'options' => $options
            ]);
            throw new \Exception('Błąd połączenia LDAP: ' . $e->getMessage());
        }
    }
    
    private function getLdapAttribute($entry, string $attributeName): ?string
    {
        if (empty($attributeName)) {
            return null;
        }
        
        $attributes = $entry->getAttributes();
        if (isset($attributes[$attributeName]) && !empty($attributes[$attributeName][0])) {
            return $attributes[$attributeName][0];
        }
        
        return null;
    }

    /**
     * Mapuje lokalizację z LDAP na wartość oddziału ze słownika
     */
    private function mapOfficeToBranch(string $office): ?string
    {
        // Podstawowe mapowanie - możesz dostosować według potrzeb
        $officeMapping = [
            'main office' => 'main_branch',
            'headquarters' => 'main_branch',
            'warsaw' => 'warsaw_branch',
            'kraków' => 'krakow_branch',
            'krakow' => 'krakow_branch',
            'gdańsk' => 'gdansk_branch',
            'gdansk' => 'gdansk_branch',
            'wrocław' => 'wroclaw_branch',
            'wroclaw' => 'wroclaw_branch',
        ];
        
        $officeLower = strtolower(trim($office));
        
        // Sprawdź bezpośrednie mapowanie
        if (isset($officeMapping[$officeLower])) {
            return $officeMapping[$officeLower];
        }
        
        // Sprawdź czy zawiera klucz
        foreach ($officeMapping as $key => $value) {
            if (strpos($officeLower, $key) !== false) {
                return $value;
            }
        }
        
        // Jeśli nie znaleziono mapowania, zwróć domyślny oddział
        return 'main_branch';
    }

    /**
     * Mapuje status z LDAP na status pracownika ze słownika
     */
    private function mapLdapStatusToEmployeeStatus(string $ldapStatus): ?string
    {
        // Active Directory userAccountControl values
        $userAccountControl = intval($ldapStatus);
        
        // Sprawdź czy konto jest wyłączone (bit 2)
        if ($userAccountControl & 2) {
            return 'inactive';
        }
        
        // Sprawdź inne bity
        if ($userAccountControl & 16) { // Account locked
            return 'inactive';
        }
        
        if ($userAccountControl & 8388608) { // Password expired
            return 'notice_period';
        }
        
        // Domyślnie aktywny
        return 'active';
    }

    /**
     * Synchronizuje hierarchię przełożonych na podstawie danych LDAP
     */
    private function syncManagerHierarchy(array $ldapSettings): array
    {
        if (empty($ldapSettings['ldap_map_manager'])) {
            return ['updated' => 0, 'errors' => 0];
        }

        $password = $ldapSettings['ldap_bind_password'];
        if (empty($password)) {
            $password = $this->settingService->get('ldap_bind_password', '');
        }

        $ldap = $this->createLdapConnection($ldapSettings, $password);
        $ldap->bind($ldapSettings['ldap_bind_dn'], $password);
        
        // Pobierz wszystkich użytkowników z LDAP wraz z informacjami o przełożonych
        $query = $ldap->query(
            $ldapSettings['ldap_base_dn'],
            $ldapSettings['ldap_user_filter'],
            ['maxItems' => 5000, 'timeout' => 60]
        );
        
        $ldapResults = $query->execute();
        
        // Utwórz mapę DN -> User
        $allUsers = $this->userRepository->findAll();
        $usersByDn = [];
        $usersByUsername = [];
        
        foreach ($allUsers as $user) {
            if ($user->getLdapDn()) {
                $usersByDn[$user->getLdapDn()] = $user;
            }
            $usersByUsername[strtolower($user->getUsername())] = $user;
        }
        
        $updated = 0;
        $errors = 0;
        
        foreach ($ldapResults as $entry) {
            try {
                $username = strtolower($this->getLdapAttribute($entry, $ldapSettings['ldap_map_username']));
                $managerDn = $this->getLdapAttribute($entry, $ldapSettings['ldap_map_manager']);
                
                if (isset($usersByUsername[$username]) && $managerDn) {
                    $user = $usersByUsername[$username];
                    
                    // Znajdź przełożonego na podstawie DN
                    if (isset($usersByDn[$managerDn])) {
                        $manager = $usersByDn[$managerDn];
                        
                        if ($user->getSupervisor() !== $manager) {
                            $user->setSupervisor($manager);
                            $this->entityManager->persist($user);
                            $this->entityManager->flush();
                            $updated++;
                            
                            $this->logger->info('Updated manager hierarchy from LDAP', [
                                'user' => $username,
                                'manager' => $manager->getUsername()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                $this->logger->error('Error updating manager hierarchy from LDAP', [
                    'username' => $username ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return [
            'updated' => $updated,
            'errors' => $errors
        ];
    }

    /**
     * Pobiera informacje o bazie danych
     */
    private function getDatabaseInfo(): array
    {
        $connection = $this->entityManager->getConnection();
        
        try {
            // Informacje podstawowe
            $databaseName = $connection->getDatabase();
            
            // Sprawdź typ bazy danych
            $platform = $connection->getDatabasePlatform();
            $databaseType = $this->getDatabaseTypeName($platform);
            
            // Pobierz rozmiar bazy danych (MySQL)
            $databaseSize = 0;
            $tableCount = 0;
            $tables = [];
            
            if ($databaseType === 'mysql') {
                // Rozmiar bazy danych
                $sizeResult = $connection->fetchAssociative("
                    SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                ", [$databaseName]);
                
                $databaseSize = $sizeResult['size_mb'] ?? 0;
                
                // Lista tabel z informacjami
                $tablesResult = $connection->fetchAllAssociative("
                    SELECT 
                        table_name AS table_name,
                        table_rows AS table_rows,
                        ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb,
                        ROUND(data_length / 1024 / 1024, 2) as data_mb,
                        ROUND(index_length / 1024 / 1024, 2) as index_mb
                    FROM information_schema.tables 
                    WHERE table_schema = ?
                    ORDER BY (data_length + index_length) DESC
                ", [$databaseName]);
                
                // Normalizuj klucze do małych liter
                $tables = [];
                foreach ($tablesResult as $row) {
                    $tables[] = [
                        'table_name' => $row['table_name'] ?? $row['TABLE_NAME'] ?? '',
                        'table_rows' => $row['table_rows'] ?? $row['TABLE_ROWS'] ?? 0,
                        'size_mb' => $row['size_mb'] ?? 0,
                        'data_mb' => $row['data_mb'] ?? 0,
                        'index_mb' => $row['index_mb'] ?? 0,
                    ];
                }
                
                $tableCount = count($tables);
            }
            
            // Wersja bazy danych
            $version = $connection->fetchOne("SELECT VERSION()");
            
            // Status połączenia
            $connectionStatus = 'connected';
            
            // Sprawdź katalog backupów
            $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
            if (!file_exists($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Lista backupów
            $backups = [];
            if (is_dir($backupDir)) {
                $backupFiles = glob($backupDir . '/backup_*.sql');
                foreach ($backupFiles as $file) {
                    $backups[] = [
                        'filename' => basename($file),
                        'size' => round(filesize($file) / 1024 / 1024, 2), // MB
                        'created' => date('Y-m-d H:i:s', filemtime($file))
                    ];
                }
                // Sortuj po dacie malejąco
                usort($backups, function($a, $b) {
                    return strcmp($b['created'], $a['created']);
                });
            }
            
            return [
                'name' => $databaseName,
                'type' => $databaseType,
                'version' => $version,
                'size_mb' => $databaseSize,
                'table_count' => $tableCount,
                'tables' => $tables,
                'connection_status' => $connectionStatus,
                'backups' => $backups,
                'backup_dir' => $backupDir
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get database info', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'name' => 'unknown',
                'type' => 'unknown',
                'version' => 'unknown',
                'size_mb' => 0,
                'table_count' => 0,
                'tables' => [],
                'connection_status' => 'error',
                'error' => $e->getMessage(),
                'backups' => [],
                'backup_dir' => ''
            ];
        }
    }
    
    /**
     * Tworzy kopię zapasową bazy danych
     */
    private function createDatabaseBackup(): string
    {
        $connection = $this->entityManager->getConnection();
        $databaseName = $connection->getDatabase();
        
        // Sprawdź czy to MySQL
        if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
            throw new \Exception('Kopie zapasowe są obsługiwane tylko dla MySQL');
        }
        
        // Przygotuj katalog
        $backupDir = $this->getParameter('kernel.project_dir') . '/var/backups';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Nazwa pliku
        $filename = 'backup_' . $databaseName . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . '/' . $filename;
        
        // Pobierz parametry połączenia z DATABASE_URL
        $databaseUrl = $_ENV['DATABASE_URL'] ?? '';
        if (preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):?(\d+)?\/([^?]+)(?:\?.*)?/', $databaseUrl, $matches)) {
            $username = $matches[1];
            $password = $matches[2];
            $host = $matches[3];
            $port = $matches[4] ?: '3306';
            $database = $matches[5]; // Teraz ignoruje parametry po znaku ?
            
            // Wykonaj mysqldump - używamy 2>&1 żeby przechwycić błędy
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s --single-transaction --routines --triggers %s > %s 2>&1',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($database),
                escapeshellarg($filepath)
            );
            
            // Loguj komendę (bez hasła) do debugowania
            $debugCommand = sprintf(
                'mysqldump -h%s -P%s -u%s -p*** --single-transaction --routines --triggers %s > %s',
                $host, $port, $username, $database, $filepath
            );
            $this->logger->info('Executing backup command', ['command' => $debugCommand]);
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                $errorMsg = implode("\n", $output);
                $this->logger->error('Backup command failed', [
                    'return_code' => $returnCode,
                    'output' => $errorMsg,
                    'database' => $database,
                    'host' => $host,
                    'port' => $port
                ]);
                throw new \Exception('Błąd podczas tworzenia kopii zapasowej: ' . $errorMsg);
            }
            
            // Sprawdź czy plik został utworzony i ma rozsądny rozmiar
            if (!file_exists($filepath)) {
                throw new \Exception('Plik kopii zapasowej nie został utworzony');
            }
            
            $fileSize = filesize($filepath);
            if ($fileSize === 0) {
                throw new \Exception('Plik kopii zapasowej jest pusty');
            }
            
            // Sprawdź czy plik zawiera jakieś dane (powinien mieć więcej niż sam nagłówek)
            if ($fileSize < 1000) { // Mniej niż 1KB to prawdopodobnie tylko nagłówek
                $fileContent = file_get_contents($filepath);
                $this->logger->warning('Backup file seems too small', [
                    'file_size' => $fileSize,
                    'content_preview' => substr($fileContent, 0, 500)
                ]);
                throw new \Exception('Kopia zapasowa wydaje się niepełna (rozmiar: ' . $fileSize . ' bajtów). Sprawdź logi dla szczegółów.');
            }
            
        } else {
            throw new \Exception('Nie można odczytać parametrów połączenia z bazą danych');
        }
        
        return $filename;
    }
    
    /**
     * Optymalizuje tabele bazy danych
     */
    private function optimizeDatabase(): array
    {
        $connection = $this->entityManager->getConnection();
        
        if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
            throw new \Exception('Optymalizacja jest obsługiwana tylko dla MySQL');
        }
        
        $databaseName = $connection->getDatabase();
        
        // Pobierz listę tabel
        $tablesResult = $connection->fetchAllAssociative("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ?
        ", [$databaseName]);
        
        // Normalizuj nazwy tabel
        $tables = [];
        foreach ($tablesResult as $row) {
            $tables[] = $row['table_name'] ?? $row['TABLE_NAME'] ?? '';
        }
        
        $optimized = 0;
        
        foreach ($tables as $table) {
            try {
                // Zabezpieczenie przed SQL injection - walidacja nazwy tabeli
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    $this->logger->warning('Invalid table name detected', ['table' => $table]);
                    continue;
                }
                $connection->executeStatement("OPTIMIZE TABLE `{$table}`");
                $optimized++;
            } catch (\Exception $e) {
                $this->logger->warning('Failed to optimize table', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['optimized' => $optimized];
    }
    
    /**
     * Analizuje tabele bazy danych
     */
    private function analyzeDatabase(): array
    {
        $connection = $this->entityManager->getConnection();
        
        if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
            throw new \Exception('Analiza jest obsługiwana tylko dla MySQL');
        }
        
        $databaseName = $connection->getDatabase();
        
        // Pobierz listę tabel
        $tablesResult = $connection->fetchAllAssociative("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = ?
        ", [$databaseName]);
        
        // Normalizuj nazwy tabel
        $tables = [];
        foreach ($tablesResult as $row) {
            $tables[] = $row['table_name'] ?? $row['TABLE_NAME'] ?? '';
        }
        
        $analyzed = 0;
        
        foreach ($tables as $table) {
            try {
                // Zabezpieczenie przed SQL injection - walidacja nazwy tabeli
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    $this->logger->warning('Invalid table name detected', ['table' => $table]);
                    continue;
                }
                $connection->executeStatement("ANALYZE TABLE `{$table}`");
                $analyzed++;
            } catch (\Exception $e) {
                $this->logger->warning('Failed to analyze table', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['analyzed' => $analyzed];
    }
    
    /**
     * Czyści stare logi (starsze niż 30 dni)
     */
    private function clearOldLogs(): array
    {
        $logDir = $this->getParameter('kernel.project_dir') . '/var/log';
        $deleted = 0;
        
        if (!is_dir($logDir)) {
            return ['deleted' => 0];
        }
        
        // Data graniczna - 30 dni wstecz
        $cutoffDate = new \DateTime('-30 days');
        
        // Przeglądaj pliki logów
        $logFiles = glob($logDir . '/*.log');
        foreach ($logFiles as $logFile) {
            $fileDate = new \DateTime('@' . filemtime($logFile));
            
            if ($fileDate < $cutoffDate) {
                try {
                    // Nie usuwaj obecnych plików logów, tylko wyczyść zawartość
                    if (basename($logFile) !== 'prod.log' && basename($logFile) !== 'dev.log') {
                        unlink($logFile);
                        $deleted++;
                    } else {
                        // Dla głównych plików logów tylko usuń stare wpisy
                        $this->truncateLogFile($logFile, $cutoffDate);
                        $deleted++;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to clear log file', [
                        'file' => $logFile,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return ['deleted' => $deleted];
    }
    
    /**
     * Skraca plik loga usuwając stare wpisy
     */
    private function truncateLogFile(string $logFile, \DateTime $cutoffDate): void
    {
        if (!file_exists($logFile)) {
            return;
        }
        
        $tempFile = $logFile . '.tmp';
        $cutoffTimestamp = $cutoffDate->getTimestamp();
        
        $input = fopen($logFile, 'r');
        $output = fopen($tempFile, 'w');
        
        if (!$input || !$output) {
            return;
        }
        
        while (($line = fgets($input)) !== false) {
            // Sprawdź czy linia zawiera timestamp
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[^\]]*)\]/', $line, $matches)) {
                $lineTimestamp = strtotime($matches[1]);
                
                // Zachowaj linie nowsze niż data graniczna
                if ($lineTimestamp >= $cutoffTimestamp) {
                    fwrite($output, $line);
                }
            } else {
                // Zachowaj linie bez timestamp (mogą być częścią poprzedniego wpisu)
                fwrite($output, $line);
            }
        }
        
        fclose($input);
        fclose($output);
        
        // Zamień pliki
        rename($tempFile, $logFile);
    }

    /**
     * Pomocnicza metoda do określenia typu bazy danych
     */
    private function getDatabaseTypeName($platform): string
    {
        // Sprawdź klasę platformy zamiast używać getName()
        if ($platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform) {
            return 'mysql';
        } elseif ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            return 'postgresql';
        } elseif ($platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            return 'sqlite';
        } elseif ($platform instanceof \Doctrine\DBAL\Platforms\SQLServerPlatform) {
            return 'mssql';
        }
        
        // Fallback - spróbuj użyć refleksji do uzyskania nazwy klasy
        $reflection = new \ReflectionClass($platform);
        $className = $reflection->getShortName();
        
        return strtolower(str_replace('Platform', '', $className));
    }
    
    /**
     * Sprawdza czy platforma to MySQL
     */
    private function isMySQLPlatform($platform): bool
    {
        return $platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform;
    }

    private function getClientIp(): ?string
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        return $request?->getClientIp();
    }
}