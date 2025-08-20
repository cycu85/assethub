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

#[Route('/admin/users')]
class UserController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private PermissionService $permissionService,
        private UserService $userService,
        private AuditService $auditService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
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

        $this->logger->info('User edit form accessed', [
            'user' => $currentUser->getUsername(),
            'ip' => $request->getClientIp(),
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername(),
            'subordinates_count' => count($subordinates)
        ]);

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'subordinates' => $subordinates,
            'has_full_permission' => $hasFullPermission,
            'has_basic_permission' => $hasBasicPermission,
            'can_edit' => $hasBasicPermission || $hasFullPermission,
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
}