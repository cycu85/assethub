<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\UserRole;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\DictionaryRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\AuthorizationService;
use App\Service\UserService;
use App\Service\AuditService;
use App\Service\LdapService;
use App\Service\SettingService;
use App\Service\EmailService;
use Symfony\Component\Ldap\Ldap;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private PermissionService $permissionService,
        private UserService $userService,
        private AuditService $auditService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private LdapService $ldapService,
        private SettingService $settingService,
        private ?EmailService $emailService = null
    ) {
    }

    #[Route('/', name: 'admin_users_index')]
    public function index(Request $request): Response {
        $user = $this->getUser();
        
        // Sprawdź uprawnienia - użyj nowej metody z AuthorizationService
        $this->authorizationService->hasAnyPermission($user, 'admin', [
            'EMPLOYEES_VIEW',
            'EMPLOYEES_EDIT_BASIC', 
            'EMPLOYEES_EDIT_FULL'
        ], $request);

        // Pobierz użytkowników z paginacją przez UserService
        $page = $request->query->getInt('page', 1);
        $filters = [
            'search' => $request->query->get('search'),
            'department' => $request->query->get('department'),
            'active' => $request->query->get('active')
        ];
        
        $users = $this->userService->getUsersWithPagination($page, 1000, $filters);

        // Pobierz słowniki przez UserService
        $dictionaries = $this->userService->getDictionariesForForms();

        // Przygotuj mapowania słowników dla template (id => nazwa)
        $branchesMap = [];
        $departmentsMap = [];
        $statusesMap = [];

        foreach ($dictionaries['branches'] as $branch) {
            $branchesMap[$branch->getValue()] = $branch->getDisplayName();
        }

        foreach ($dictionaries['departments'] as $department) {
            $departmentsMap[$department->getValue()] = $department->getDisplayName();
        }

        foreach ($dictionaries['statuses'] as $status) {
            $statusesMap[$status->getValue()] = $status->getDisplayName();
        }

        // Loguj dostęp przez AuditService
        $this->auditService->logUserAction($user, 'view_users_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_users' => $users->getTotalItemCount()
        ], $request);

        // Check user permissions for template  
        $canEdit = $this->authorizationService->hasPermission($user, 'admin', 'EMPLOYEES_EDIT_BASIC') || 
                   $this->authorizationService->hasPermission($user, 'admin', 'EMPLOYEES_EDIT_FULL');
        $canEditFull = $this->authorizationService->hasPermission($user, 'admin', 'EMPLOYEES_EDIT_FULL');
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'can_edit' => $canEdit,
            'can_edit_full' => $canEditFull,
            'dictionaries' => $dictionaries,
            'branches_map' => $branchesMap,
            'departments_map' => $departmentsMap,
            'statuses_map' => $statusesMap,
            'statistics' => $this->userService->getUserStatistics()
        ]);
    }

    #[Route('/{id}/roles', name: 'admin_users_roles', requirements: ['id' => '\d+'])]
    public function manageRoles(Request $request, User $user, RoleRepository $roleRepository): Response
    {
        $currentUser = $this->getUser();
        
        if (!$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL')) {
            $this->logger->warning('Unauthorized user roles management access attempt', [
                'user' => $currentUser?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId()
            ]);
            return $this->redirectToRoute('error_access_denied');
        }

        if ($request->isMethod('POST')) {
            $selectedRoles = $request->request->all('roles') ?? [];
            
            // Deactivate all current roles
            foreach ($user->getUserRoles() as $userRole) {
                $userRole->setIsActive(false);
            }
            
            // Add new roles
            foreach ($selectedRoles as $roleId) {
                $role = $roleRepository->find($roleId);
                if ($role) {
                    // Check if user already has this role
                    $existingUserRole = null;
                    foreach ($user->getUserRoles() as $userRole) {
                        if ($userRole->getRole()->getId() === $role->getId()) {
                            $existingUserRole = $userRole;
                            break;
                        }
                    }
                    
                    if ($existingUserRole) {
                        $existingUserRole->setIsActive(true);
                    } else {
                        $userRole = new UserRole();
                        $userRole->setUser($user);
                        $userRole->setRole($role);
                        $userRole->setAssignedBy($currentUser);
                        $user->addUserRole($userRole);
                        $this->entityManager->persist($userRole);
                    }
                }
            }
            
            $this->entityManager->flush();
            
            $this->logger->info('User roles updated successfully', [
                'user' => $currentUser->getUsername(),
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername(),
                'assigned_roles' => $selectedRoles
            ]);
            
            $this->addFlash('success', 'Role użytkownika zostały zaktualizowane.');
            return $this->redirectToRoute('admin_users_index');
        }

        $allRoles = $roleRepository->findAll();
        $userActiveRoles = [];
        
        foreach ($user->getUserRoles() as $userRole) {
            if ($userRole->isActive()) {
                $userActiveRoles[] = $userRole->getRole()->getId();
            }
        }

        $this->logger->info('User roles management form accessed', [
            'user' => $currentUser->getUsername(),
            'ip' => $request->getClientIp(),
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername()
        ]);

        return $this->render('admin/users/roles.html.twig', [
            'user' => $user,
            'allRoles' => $allRoles,
            'userActiveRoles' => $userActiveRoles,
        ]);
    }

    #[Route('/new', name: 'admin_users_new')]
    public function new(Request $request, UserPasswordHasherInterface $passwordHasher): Response
    {
        $currentUser = $this->getUser();
        
        if (!$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL')) {
            $this->logger->warning('Unauthorized user create access attempt', [
                'user' => $currentUser?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp()
            ]);
            return $this->redirectToRoute('error_access_denied');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);
            
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('User created successfully', [
                'user' => $currentUser->getUsername(),
                'ip' => $request->getClientIp(),
                'created_user_id' => $user->getId(),
                'created_username' => $user->getUsername(),
                'created_email' => $user->getEmail()
            ]);

            $this->addFlash('success', 'Użytkownik został utworzony pomyślnie.');

            return $this->redirectToRoute('admin_users_index');
        }

        $this->logger->info('User new form accessed', [
            'user' => $currentUser->getUsername(),
            'ip' => $request->getClientIp()
        ]);

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_users_show', requirements: ['id' => '\d+'])]
    public function show(Request $request, User $user, UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        
        if (!$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_VIEW') && 
            !$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_BASIC') && 
            !$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL')) {
            $this->logger->warning('Unauthorized user show access attempt', [
                'user' => $currentUser?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId()
            ]);
            return $this->redirectToRoute('error_access_denied');
        }

        // Pobierz podwładnych tego użytkownika
        $subordinates = $userRepository->findSubordinates($user);

        $this->logger->info('User details viewed', [
            'user' => $currentUser->getUsername(),
            'ip' => $request->getClientIp(),
            'viewed_user_id' => $user->getId(),
            'viewed_username' => $user->getUsername(),
            'subordinates_count' => count($subordinates)
        ]);

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'subordinates' => $subordinates,
            'can_edit' => $this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_BASIC') ||
                         $this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL'),
            'can_edit_full' => $this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL'),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_users_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        
        if (!$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_BASIC') && 
            !$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL')) {
            $this->logger->warning('Unauthorized user edit access attempt', [
                'user' => $currentUser?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId()
            ]);
            return $this->redirectToRoute('error_access_denied');
        }

        // Determine user's permission level for form options
        $hasFullPermission = $this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL');
        $hasBasicPermission = $this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_BASIC');
        
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
            'current_user_id' => $user->getId(), // Exclude current user from supervisor list
            'allow_username_edit' => $hasFullPermission,
            'allow_password_edit' => $hasFullPermission,
            'allow_status_edit' => $hasFullPermission
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password only if new password was provided and user has permission to change it
            if ($hasFullPermission && $form->has('plainPassword')) {
                $plainPassword = $form->get('plainPassword')->getData();
                if (!empty($plainPassword)) {
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }
            }
            
            $this->entityManager->flush();

            $this->logger->info('User updated successfully', [
                'user' => $currentUser->getUsername(),
                'ip' => $request->getClientIp(),
                'updated_user_id' => $user->getId(),
                'updated_username' => $user->getUsername(),
                'updated_email' => $user->getEmail(),
                'password_changed' => !empty($plainPassword)
            ]);

            $this->addFlash('success', 'Dane użytkownika zostały zaktualizowane pomyślnie.');

            return $this->redirectToRoute('admin_users_index');
        }

        // Pobierz podwładnych tego użytkownika
        $subordinates = $userRepository->findSubordinates($user);

        // Pobierz dane LDAP dla system_admin - tylko jeśli użytkownik ma LDAP DN
        $ldapData = [];
        $isSystemAdmin = $this->isSystemAdmin($currentUser);
        $isLdapUser = !empty($user->getLdapDn());
        
        if ($isSystemAdmin && $isLdapUser) {
            $ldapData = $this->getLdapUserData($user);
        }

        $this->logger->info('User edit form accessed', [
            'user' => $currentUser->getUsername(),
            'ip' => $request->getClientIp(),
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername(),
            'subordinates_count' => count($subordinates),
            'ldap_data_loaded' => !empty($ldapData)
        ]);

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'subordinates' => $subordinates,
            'has_full_permission' => $hasFullPermission,
            'has_basic_permission' => $hasBasicPermission,
            'can_edit' => $hasBasicPermission || $hasFullPermission,
            'ldap_data' => $ldapData,
            'is_system_admin' => $isSystemAdmin,
            'is_ldap_user' => $isLdapUser,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();
        
        if (!$this->permissionService->hasPermission($currentUser, 'admin', 'EMPLOYEES_EDIT_FULL')) {
            $this->logger->warning('Unauthorized user toggle status access attempt', [
                'user' => $currentUser?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId()
            ]);
            return $this->redirectToRoute('error_access_denied');
        }

        if ($this->isCsrfTokenValid('toggle_status'.$user->getId(), $request->request->get('_token'))) {
            $oldStatus = $user->isActive();
            $user->setIsActive(!$user->isActive());
            $this->entityManager->flush();

            $status = $user->isActive() ? 'aktywowany' : 'dezaktywowany';
            
            $this->logger->info('User status toggled', [
                'user' => $currentUser->getUsername(),
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername(),
                'old_status' => $oldStatus,
                'new_status' => $user->isActive()
            ]);
            
            $this->addFlash('success', "Użytkownik został {$status}.");
        } else {
            $this->logger->warning('User toggle status attempt with invalid CSRF token', [
                'user' => $currentUser->getUsername(),
                'ip' => $request->getClientIp(),
                'target_user_id' => $user->getId()
            ]);
        }

        return $this->redirectToRoute('admin_users_index');
    }

    #[Route('/{id}/ldap/unlock', name: 'admin_users_ldap_unlock', methods: ['POST'])]
    public function ldapUnlock(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();
        
        // Sprawdź uprawnienia - tylko system_admin
        if (!$this->isSystemAdmin($currentUser)) {
            $this->auditService->logSecurityEvent('unauthorized_ldap_unlock_attempt', $currentUser, [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername()
            ], $request);
            
            return $this->json(['success' => false, 'message' => 'Brak uprawnień do tej operacji'], 403);
        }

        try {
            // Sprawdź czy to konto LDAP
            if (empty($user->getLdapDn())) {
                return $this->json(['success' => false, 'message' => 'To nie jest konto LDAP'], 400);
            }

            // Odblokuj konto w LDAP/AD
            $settings = $this->getLdapSettings();
            
            if (!$settings['ldap_enabled']) {
                throw new \Exception('LDAP integration is disabled');
            }
            
            $ldap = $this->ldapService->createLdapConnection($settings);
            $this->bindServiceUser($ldap, $settings);
            
            $ldapDn = $user->getLdapDn();
            
            // Pobierz aktualny userAccountControl
            $query = $ldap->query($ldapDn, '(objectClass=*)');
            $results = $query->execute();
            
            if (count($results) === 0) {
                throw new \Exception('User not found in LDAP');
            }
            
            $entry = $results[0];
            $userAccountControlAttr = $entry->getAttribute('userAccountControl');
            $userAccountControl = (int)($userAccountControlAttr ? $userAccountControlAttr[0] : 0);
            
            // Usuń flagi ACCOUNTDISABLE i LOCKOUT
            $newUserAccountControl = $userAccountControl & ~0x0002 & ~0x0010;
            
            // Zaktualizuj LDAP
            $ldap->getEntryManager()->update($entry, [
                'userAccountControl' => [$newUserAccountControl]
            ]);
            
            $this->auditService->logUserAction($currentUser, 'ldap_account_unlocked', [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername()
            ], $request);

            $this->logger->info('LDAP account unlocked', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'ip' => $request->getClientIp()
            ]);

            return $this->json(['success' => true, 'message' => 'Konto zostało odblokowane']);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to unlock LDAP account', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json(['success' => false, 'message' => 'Wystąpił błąd podczas odblokowywania konta'], 500);
        }
    }

    #[Route('/{id}/ldap/reset-password', name: 'admin_users_ldap_reset_password', methods: ['POST'])]
    public function ldapResetPassword(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();
        
        // Sprawdź uprawnienia - tylko system_admin
        if (!$this->isSystemAdmin($currentUser)) {
            $this->auditService->logSecurityEvent('unauthorized_ldap_password_reset_attempt', $currentUser, [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername()
            ], $request);
            
            return $this->json(['success' => false, 'message' => 'Brak uprawnień do tej operacji'], 403);
        }

        try {
            // Sprawdź czy to konto LDAP
            if (empty($user->getLdapDn())) {
                return $this->json(['success' => false, 'message' => 'To nie jest konto LDAP'], 400);
            }

            // Pobierz parametry z requestu
            $requestData = json_decode($request->getContent(), true);
            $sendEmail = $requestData['send_email'] ?? false;
            $customPassword = $requestData['custom_password'] ?? null;

            // Użyj niestandardowego hasła lub wygeneruj nowe
            if (!empty($customPassword)) {
                // Bez walidacji - pozwól na dowolne hasło (do testowania)
                $newPassword = $customPassword;
            } else {
                // Generuj nowe tymczasowe hasło
                $newPassword = $this->generateTemporaryPassword();
            }
            
            // Resetuj hasło w LDAP/AD
            $settings = $this->getLdapSettings();
            
            if (!$settings['ldap_enabled']) {
                throw new \Exception('LDAP integration is disabled');
            }
            
            $ldap = $this->ldapService->createLdapConnection($settings);
            $this->bindServiceUser($ldap, $settings);
            
            $ldapDn = $user->getLdapDn();
                
            // Pobierz entry użytkownika
            $query = $ldap->query($ldapDn, '(objectClass=*)');
            $results = $query->execute();
            
            if (count($results) === 0) {
                throw new \Exception('User not found in LDAP');
            }
                
            $entry = $results[0];
            
            // Ustaw nowe hasło (w AD hasło musi być w formacie UTF-16LE z cudzysłowami)
            $encodedPassword = mb_convert_encoding('"' . $newPassword . '"', 'UTF-16LE');
            
            // Sprawdź atrybuty użytkownika przed zmianą hasła
            $userAttributes = $entry->getAttributes();
            $userAccountControl = $userAttributes['userAccountControl'][0] ?? 0;
            
            $this->logger->info('Attempting LDAP password reset', [
                'target_dn' => $ldapDn,
                'password_length' => strlen($newPassword),
                'encoded_length' => strlen($encodedPassword),
                'user' => $currentUser->getUsername(),
                'userAccountControl' => $userAccountControl,
                'account_disabled' => ($userAccountControl & 0x0002) ? 'yes' : 'no',
                'password_never_expires' => ($userAccountControl & 0x10000) ? 'yes' : 'no',
                'password_cannot_change' => ($userAccountControl & 0x0040) ? 'yes' : 'no',
                'entry_attributes' => array_keys($userAttributes)
            ]);
            
            // Spróbuj różnych podejść do resetowania hasła
            try {
                // Podejście 1: Tylko unicodePwd
                $this->logger->info('Trying approach 1: unicodePwd only');
                $ldap->getEntryManager()->update($entry, [
                    'unicodePwd' => [$encodedPassword]
                ]);
                $this->logger->info('Password reset successful with approach 1');
            } catch (\Exception $e1) {
                $this->logger->warning('Approach 1 failed', ['error' => $e1->getMessage()]);
                
                try {
                    // Podejście 2: unicodePwd + pwdLastSet = 0 (wymusza zmianę przy następnym logowaniu)
                    $this->logger->info('Trying approach 2: unicodePwd + pwdLastSet = 0');
                    $ldap->getEntryManager()->update($entry, [
                        'unicodePwd' => [$encodedPassword],
                        'pwdLastSet' => ['0']
                    ]);
                    $this->logger->info('Password reset successful with approach 2');
                } catch (\Exception $e2) {
                    $this->logger->warning('Approach 2 failed', ['error' => $e2->getMessage()]);
                    
                    try {
                        // Podejście 3: unicodePwd + pwdLastSet = -1 (hasło nie wygasa)
                        $this->logger->info('Trying approach 3: unicodePwd + pwdLastSet = -1');
                        $ldap->getEntryManager()->update($entry, [
                            'unicodePwd' => [$encodedPassword],
                            'pwdLastSet' => ['-1']
                        ]);
                        $this->logger->info('Password reset successful with approach 3');
                    } catch (\Exception $e3) {
                        $this->logger->error('All approaches failed', [
                            'approach1_error' => $e1->getMessage(),
                            'approach2_error' => $e2->getMessage(), 
                            'approach3_error' => $e3->getMessage()
                        ]);
                        throw $e3;
                    }
                }
            }
            
            $this->auditService->logSecurityEvent('ldap_password_reset', $currentUser, [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername(),
                'reset_by' => $currentUser->getUsername()
            ], $request, 'high');

            $this->logger->info('LDAP password reset', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'ip' => $request->getClientIp()
            ]);

            // Wyślij email z nowym hasłem jeśli zaznaczono checkbox i EmailService jest dostępny
            $emailSent = false;
            $emailError = null;
            
            if ($sendEmail && $this->emailService && $user->getEmail()) {
                try {
                    $emailSent = $this->emailService->sendEmail(
                        to: $user->getEmail(),
                        subject: 'Twoje hasło zostało zresetowane - ' . $this->settingService->get('app_name', 'AssetHub'),
                        body: $this->buildPasswordResetEmailBody($user, $newPassword),
                        toName: $user->getFullName(),
                        emailType: 'ldap_password_reset',
                        metadata: [
                            'user_id' => $user->getId(),
                            'user_username' => $user->getUsername(),
                            'reset_by_admin' => $currentUser->getUsername(),
                            'reset_by_id' => $currentUser->getId()
                        ]
                    );

                    if ($emailSent) {
                        $this->auditService->logUserAction($currentUser, 'ldap_password_reset_email_sent', [
                            'target_user_id' => $user->getId(),
                            'target_email' => $user->getEmail()
                        ], $request);
                    }
                } catch (\Exception $e) {
                    $emailError = $e->getMessage();
                    $this->logger->warning('Failed to send password reset email', [
                        'admin_user' => $currentUser->getUsername(),
                        'target_user' => $user->getUsername(),
                        'target_email' => $user->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($sendEmail && !$user->getEmail()) {
                $emailError = 'Użytkownik nie ma podanego adresu email';
            }

            $response = [
                'success' => true, 
                'message' => 'Hasło zostało zresetowane',
                'new_password' => $newPassword,
                'email_sent' => $emailSent
            ];

            if ($emailError) {
                $response['email_error'] = $emailError;
            }

            return $this->json($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to reset LDAP password', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json(['success' => false, 'message' => 'Wystąpił błąd podczas resetowania hasła'], 500);
        }
    }

    #[Route('/{id}/ldap/refresh', name: 'admin_users_ldap_refresh', methods: ['POST'])]
    public function ldapRefresh(Request $request, User $user): Response
    {
        $currentUser = $this->getUser();
        
        // Sprawdź uprawnienia - tylko system_admin
        if (!$this->isSystemAdmin($currentUser)) {
            $this->auditService->logSecurityEvent('unauthorized_ldap_refresh_attempt', $currentUser, [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername()
            ], $request);
            
            return $this->json(['success' => false, 'message' => 'Brak uprawnień do tej operacji'], 403);
        }

        try {
            // Sprawdź czy to konto LDAP
            if (empty($user->getLdapDn())) {
                return $this->json(['success' => false, 'message' => 'To nie jest konto LDAP'], 400);
            }

            // Odśwież dane z LDAP/AD - po prostu wywołaj ponownie getLdapUserData
            $freshLdapData = $this->getLdapUserData($user);
            
            $this->auditService->logUserAction($currentUser, 'ldap_data_refreshed', [
                'target_user_id' => $user->getId(),
                'target_username' => $user->getUsername()
            ], $request);

            $this->logger->info('LDAP data refreshed', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'ip' => $request->getClientIp()
            ]);

            return $this->json(['success' => true, 'message' => 'Dane LDAP zostały odświeżone']);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh LDAP data', [
                'admin_user' => $currentUser->getUsername(),
                'target_user' => $user->getUsername(),
                'error' => $e->getMessage(),
                'ip' => $request->getClientIp()
            ]);
            
            return $this->json(['success' => false, 'message' => 'Wystąpił błąd podczas odświeżania danych LDAP'], 500);
        }
    }

    /**
     * Sprawdza czy użytkownik ma rolę system_admin
     */
    private function isSystemAdmin(User $user): bool
    {
        foreach ($user->getUserRoles() as $userRole) {
            if ($userRole->isActive() && $userRole->getRole()->getName() === 'system_admin') {
                return true;
            }
        }
        return false;
    }

    /**
     * Pobiera dane użytkownika z LDAP/Active Directory
     */
    private function getLdapUserData(User $user): array
    {
        if (!$user->getLdapDn()) {
            return ['error' => 'User is not an LDAP user'];
        }

        try {
            // Pobierz ustawienia LDAP
            $settings = $this->getLdapSettings();
            
            if (!$settings['ldap_enabled']) {
                return ['error' => 'LDAP integration is disabled'];
            }
            
            // Pobierz dane użytkownika z LDAP
            $ldapUserData = $this->ldapService->getLdapUser($user->getUsername(), $settings);
            
            if (!$ldapUserData) {
                return ['error' => 'User not found in LDAP'];
            }
            
            $attributes = $ldapUserData['attributes'];
            
            // Parsuj dane Active Directory z bezpiecznym dostępem
            $pwdLastSet = isset($attributes['pwdLastSet']) ? $attributes['pwdLastSet'][0] : null;
            $lastLogon = isset($attributes['lastLogon']) ? $attributes['lastLogon'][0] : null;
            $badPasswordTime = isset($attributes['badPasswordTime']) ? $attributes['badPasswordTime'][0] : null;
            $userAccountControl = isset($attributes['userAccountControl']) ? (int)$attributes['userAccountControl'][0] : 0;
            $badPwdCount = isset($attributes['badPwdCount']) ? (int)$attributes['badPwdCount'][0] : 0;
            
            return [
                'password_expires' => $this->parseAdDate($pwdLastSet, '+90 days'),
                'password_last_set' => $this->parseAdDate($pwdLastSet),
                'last_successful_login' => $this->parseAdDate($lastLogon),
                'last_failed_login' => $this->parseAdDate($badPasswordTime),
                'account_locked_by_failed_logins' => $this->isAccountLockedByFailedLogins($userAccountControl),
                'account_disabled' => $this->isAccountDisabled($userAccountControl),
                'failed_login_count' => $badPwdCount,
                'last_updated' => new \DateTime()
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Error fetching LDAP user data', [
                'user_id' => $user->getId(),
                'username' => $user->getUsername(),
                'ldap_dn' => $user->getLdapDn(),
                'error' => $e->getMessage()
            ]);
            
            return ['error' => 'LDAP connection error: ' . $e->getMessage()];
        }
    }

    /**
     * Parsuje datę z formatu Active Directory
     */
    private function parseAdDate(?string $adTimestamp, string $addInterval = null): ?\DateTime
    {
        if (!$adTimestamp || $adTimestamp == '0' || $adTimestamp == '9223372036854775807') {
            return null;
        }
        
        try {
            // AD timestamps są w formacie 100-nanosecond intervals od 1601-01-01
            $unixTimestamp = ($adTimestamp / 10000000) - 11644473600;
            $date = new \DateTime('@' . $unixTimestamp);
            
            if ($addInterval) {
                $date->modify($addInterval);
            }
            
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Sprawdza czy konto jest zablokowane przez błędne próby logowania
     */
    private function isAccountLockedByFailedLogins(int $userAccountControl): bool
    {
        // Bit 0x0010 = LOCKOUT (konto zablokowane przez błędne hasła)
        return ($userAccountControl & 0x0010) !== 0;
    }

    /**
     * Sprawdza czy konto jest wyłączone
     */
    private function isAccountDisabled(int $userAccountControl): bool
    {
        // Bit 0x0002 = ACCOUNTDISABLE (konto wyłączone)
        return ($userAccountControl & 0x0002) !== 0;
    }

    /**
     * Generuje bezpieczne tymczasowe hasło zgodne z polityką Active Directory
     */
    private function generateTemporaryPassword(): string
    {
        $length = 14; // Dłuższe hasło dla większej złożoności
        
        // Kategorie znaków zgodne z AD
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnpqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        
        $password = '';
        
        // Zapewnij co najmniej jeden znak z każdej kategorii (wymagane przez AD)
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        // Wypełnij pozostałe pozycje losowymi znakami
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        // Przemieszaj znaki aby uniknąć przewidywalnej struktury
        $passwordArray = str_split($password);
        shuffle($passwordArray);
        
        return implode('', $passwordArray);
    }
    
    /**
     * Pobiera ustawienia LDAP z serwisu ustawień
     */
    private function getLdapSettings(): array
    {
        return [
            'ldap_enabled' => (bool) $this->settingService->get('ldap_enabled'),
            'ldap_host' => $this->settingService->get('ldap_host'),
            'ldap_port' => (int) $this->settingService->get('ldap_port'),
            'ldap_encryption' => $this->settingService->get('ldap_encryption'),
            'ldap_bind_dn' => $this->settingService->get('ldap_bind_dn'),
            'ldap_bind_password' => $this->settingService->get('ldap_bind_password'),
            'ldap_base_dn' => $this->settingService->get('ldap_base_dn'),
            'ldap_user_filter' => $this->settingService->get('ldap_user_filter'),
            'ldap_map_username' => $this->settingService->get('ldap_map_username'),
            'ldap_map_email' => $this->settingService->get('ldap_map_email'),
            'ldap_map_firstname' => $this->settingService->get('ldap_map_firstname'),
            'ldap_map_lastname' => $this->settingService->get('ldap_map_lastname'),
            'ldap_ignore_ssl_cert' => (bool) $this->settingService->get('ldap_ignore_ssl_cert'),
        ];
    }
    
    /**
     * Binduje service user do LDAP
     */
    private function bindServiceUser($ldap, array $settings): void
    {
        $searchDn = $settings['ldap_bind_dn'] ?? '';
        $searchPassword = $settings['ldap_bind_password'] ?? '';

        if (!$searchDn || !$searchPassword) {
            throw new \Exception('LDAP credentials not configured');
        }

        $ldap->bind($searchDn, $searchPassword);
    }

    /**
     * Buduje treść emaila z nowym hasłem
     */
    private function buildPasswordResetEmailBody(User $user, string $newPassword): string
    {
        $appName = $this->settingService->get('app_name', 'AssetHub');
        
        $body = "Witaj {$user->getFirstName()}!\n\n";
        $body .= "Twoje hasło w systemie {$appName} zostało zresetowane przez administratora.\n\n";
        $body .= "Nowe tymczasowe hasło: {$newPassword}\n\n";
        $body .= "UWAGA:\n";
        $body .= "- To hasło jest tymczasowe i musisz je zmienić przy pierwszym logowaniu\n";
        $body .= "- Zachowaj to hasło w bezpiecznym miejscu\n";
        $body .= "- Ten email zostanie automatycznie usunięty z serwera po 90 dniach\n\n";
        $body .= "Jeśli nie prosiłeś o reset hasła, skontaktuj się z administratorem.\n\n";
        $body .= "Pozdrawiamy,\nZespół {$appName}";

        return $body;
    }
}