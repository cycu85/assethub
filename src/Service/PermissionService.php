<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ModuleRepository;
use App\Repository\UserRoleRepository;
use Psr\Log\LoggerInterface;

class PermissionService
{
    public function __construct(
        private UserRoleRepository $userRoleRepository,
        private ModuleRepository $moduleRepository,
        private LoggerInterface $logger
    ) {
    }

    public function hasPermission(User $user, string $module, string $permission): bool
    {
        $userRoles = $this->userRoleRepository->findActiveByUser($user->getId());
        
        $this->logger->info('Checking permission', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'module' => $module,
            'permission' => $permission,
            'user_roles_count' => count($userRoles)
        ]);
        
        foreach ($userRoles as $userRole) {
            $role = $userRole->getRole();
            $roleName = $role->getName();
            $roleModule = $role->getModule()->getName();
            $rolePermissions = $role->getPermissions();
            
            $this->logger->info('Checking role', [
                'role_name' => $roleName,
                'role_module' => $roleModule,
                'role_permissions' => $rolePermissions,
                'target_module' => $module,
                'target_permission' => $permission
            ]);
            
            // System admin ma pełne uprawnienia do wszystkich modułów
            if ($roleName === 'system_admin') {
                $this->logger->info('Permission granted - system admin override', [
                    'user' => $user->getUsername(),
                    'role' => $roleName,
                    'module' => $module,
                    'permission' => $permission
                ]);
                return true;
            }
            
            if ($roleModule === $module) {
                if ($role->hasPermission($permission)) {
                    $this->logger->info('Permission granted', [
                        'user' => $user->getUsername(),
                        'role' => $roleName,
                        'module' => $module,
                        'permission' => $permission
                    ]);
                    return true;
                }
            }
        }
        
        $this->logger->warning('Permission denied', [
            'user' => $user->getUsername(),
            'module' => $module,
            'permission' => $permission
        ]);
        
        return false;
    }

    public function getUserModules(User $user): array
    {
        $userRoles = $this->userRoleRepository->findActiveByUser($user->getId());
        $moduleUserRoles = [];
        $seenModules = [];
        
        // Sprawdź czy użytkownik jest system_admin
        $isSystemAdmin = false;
        foreach ($userRoles as $userRole) {
            if ($userRole->getRole()->getName() === 'system_admin') {
                $isSystemAdmin = true;
                break;
            }
        }
        
        // Jeśli system_admin, zwróć wszystkie aktywne moduły
        if ($isSystemAdmin) {
            $allModules = $this->moduleRepository->findBy(['isEnabled' => true]);
            foreach ($allModules as $module) {
                // Znajdź pierwszą rolę dla tego modułu (potrzebne dla kompatybilności)
                foreach ($userRoles as $userRole) {
                    if ($userRole->getRole()->getModule()->getId() === $module->getId()) {
                        if (!in_array($module->getId(), $seenModules, true)) {
                            $moduleUserRoles[] = $userRole;
                            $seenModules[] = $module->getId();
                        }
                        break;
                    }
                }
                
                // Jeśli nie ma roli dla modułu, utwórz "wirtualną" rolę system_admin dla tego modułu
                if (!in_array($module->getId(), $seenModules, true)) {
                    foreach ($userRoles as $userRole) {
                        if ($userRole->getRole()->getName() === 'system_admin') {
                            $moduleUserRoles[] = $userRole; // Użyj system_admin jako reprezentatywną rolę
                            $seenModules[] = $module->getId();
                            break;
                        }
                    }
                }
            }
        } else {
            // Standardowa logika dla innych użytkowników
            foreach ($userRoles as $userRole) {
                $module = $userRole->getRole()->getModule();
                if ($module->isEnabled() && !in_array($module->getId(), $seenModules, true)) {
                    $moduleUserRoles[] = $userRole;
                    $seenModules[] = $module->getId();
                }
            }
        }
        
        return $moduleUserRoles;
    }

    public function getModulePermissions(User $user, string $moduleName): array
    {
        $userRoles = $this->userRoleRepository->findActiveByUser($user->getId());
        $permissions = [];
        
        foreach ($userRoles as $userRole) {
            $role = $userRole->getRole();
            if ($role->getModule()->getName() === $moduleName) {
                $permissions = array_unique(array_merge($permissions, $role->getPermissions()));
            }
        }
        
        return $permissions;
    }

    public function canAccessModule(User $user, string $moduleName): bool
    {
        $module = $this->moduleRepository->findByName($moduleName);
        
        if (!$module || !$module->isEnabled()) {
            return false;
        }
        
        // System admin ma dostęp do wszystkich modułów
        $userRoles = $this->userRoleRepository->findActiveByUser($user->getId());
        foreach ($userRoles as $userRole) {
            if ($userRole->getRole()->getName() === 'system_admin') {
                return true;
            }
        }
        
        $userModules = $this->getUserModules($user);
        
        foreach ($userModules as $userRole) {
            if ($userRole->getRole()->getModule()->getName() === $moduleName) {
                return true;
            }
        }
        
        return false;
    }

    public static function getAvailablePermissions(): array
    {
        return [
            'VIEW' => 'Podgląd',
            'CREATE' => 'Tworzenie',
            'EDIT' => 'Edycja',
            'DELETE' => 'Usuwanie',
            'ASSIGN' => 'Przypisywanie',
            'REVIEW' => 'Przeglądy',
            'EXPORT' => 'Eksport',
            'CONFIGURE' => 'Konfiguracja',
            'EMPLOYEES_VIEW' => 'Przeglądanie pracowników',
            'EMPLOYEES_EDIT_BASIC' => 'Edycja podstawowych danych pracowników',
            'EMPLOYEES_EDIT_FULL' => 'Pełna edycja pracowników'
        ];
    }
}