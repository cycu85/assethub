<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\UnauthorizedAccessException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AuthorizationService
{
    public function __construct(
        private PermissionService $permissionService,
        private LoggerInterface $logger,
        private CacheInterface $permissionsCache
    ) {
    }

    /**
     * Sprawdza dostęp do modułu i rzuca wyjątek w przypadku braku uprawnień
     */
    public function checkModuleAccess(?User $user, string $module, Request $request): void
    {
        if (!$user) {
            $this->logUnauthorizedAccess(null, "No user authenticated for module: $module", $request);
            throw new UnauthorizedAccessException('Musisz być zalogowany aby uzyskać dostęp');
        }

        if (!$this->permissionService->canAccessModule($user, $module)) {
            $this->logUnauthorizedAccess($user, "Module access denied: $module", $request);
            throw new UnauthorizedAccessException('Brak dostępu do tego modułu');
        }
    }

    /**
     * Sprawdza konkretne uprawnienie i rzuca wyjątek w przypadku braku dostępu
     */
    public function checkPermission(?User $user, string $module, string $permission, Request $request): void
    {
        if (!$user) {
            $this->logUnauthorizedAccess(null, "No user authenticated for permission: $module.$permission", $request);
            throw new UnauthorizedAccessException('Musisz być zalogowany aby uzyskać dostęp');
        }

        if (!$this->hasPermission($user, $module, $permission)) {
            $this->logUnauthorizedAccess($user, "Permission denied: $module.$permission", $request);
            throw new UnauthorizedAccessException('Brak wymaganych uprawnień');
        }
    }

    /**
     * Sprawdza czy użytkownik ma określone uprawnienie (z cache)
     */
    public function hasPermission(User $user, string $module, string $permission): bool
    {
        $cacheKey = sprintf('user_permission_%d_%s_%s', $user->getId(), $module, $permission);

        return $this->permissionsCache->get($cacheKey, function (ItemInterface $item) use ($user, $module, $permission) {
            $item->expiresAfter(1800); // 30 minut
            return $this->permissionService->hasPermission($user, $module, $permission);
        });
    }

    /**
     * Sprawdza czy użytkownik może uzyskać dostęp do modułu (z cache)
     */
    public function canAccessModule(User $user, string $module): bool
    {
        $cacheKey = sprintf('user_module_%d_%s', $user->getId(), $module);

        return $this->permissionsCache->get($cacheKey, function (ItemInterface $item) use ($user, $module) {
            $item->expiresAfter(1800); // 30 minut
            return $this->permissionService->canAccessModule($user, $module);
        });
    }

    /**
     * Pobiera listę modułów dostępnych dla użytkownika (z cache)
     */
    public function getUserModules(User $user): array
    {
        $cacheKey = sprintf('user_modules_%d', $user->getId());

        return $this->permissionsCache->get($cacheKey, function (ItemInterface $item) use ($user) {
            $item->expiresAfter(1800); // 30 minut
            return $this->permissionService->getUserModules($user);
        });
    }

    /**
     * Sprawdza czy użytkownik ma jedną z wielu uprawnień
     */
    public function hasAnyPermission(User $user, string $module, array $permissions, Request $request): void
    {
        if (!$user) {
            $this->logUnauthorizedAccess(null, "No user authenticated for any permission in: $module", $request);
            throw new UnauthorizedAccessException('Musisz być zalogowany aby uzyskać dostęp');
        }

        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $module, $permission)) {
                return; // Ma co najmniej jedno uprawnienie
            }
        }

        $this->logUnauthorizedAccess($user, "None of required permissions found: $module.[" . implode(', ', $permissions) . "]", $request);
        throw new UnauthorizedAccessException('Brak wymaganych uprawnień');
    }

    /**
     * Sprawdza czy użytkownik jest właścicielem zasobu lub ma uprawnienia administracyjne
     */
    public function checkResourceOwnership(?User $user, $resource, string $ownerProperty = 'user', string $adminPermission = 'ADMIN', Request $request): void
    {
        if (!$user) {
            $this->logUnauthorizedAccess(null, "No user authenticated for resource access", $request);
            throw new UnauthorizedAccessException('Musisz być zalogowany aby uzyskać dostęp');
        }

        // Sprawdź czy to właściciel zasobu
        $owner = null;
        if (is_object($resource) && method_exists($resource, 'get' . ucfirst($ownerProperty))) {
            $getter = 'get' . ucfirst($ownerProperty);
            $owner = $resource->$getter();
        }

        if ($owner && $owner->getId() === $user->getId()) {
            return; // Jest właścicielem
        }

        // Sprawdź czy ma uprawnienia administracyjne
        if ($this->hasPermission($user, 'admin', $adminPermission)) {
            return; // Ma uprawnienia administracyjne
        }

        $this->logUnauthorizedAccess($user, "Resource access denied - not owner and no admin permissions", $request);
        throw new UnauthorizedAccessException('Brak dostępu do tego zasobu');
    }

    /**
     * Invaliduje cache uprawnień dla użytkownika
     */
    public function invalidateUserPermissionsCache(User $user): void
    {
        // W rzeczywistej implementacji można by usunąć wszystkie klucze cache dla użytkownika
        // Na razie logujemy akcję
        $this->logger->info('User permissions cache invalidated', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername()
        ]);
    }

    /**
     * Loguje próby nieautoryzowanego dostępu
     */
    public function logUnauthorizedAccess(?User $user, string $context, Request $request): void
    {
        $this->logger->warning('Unauthorized access attempt', [
            'user' => $user ? $user->getUsername() : 'anonymous',
            'user_id' => $user?->getId(),
            'context' => $context,
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'url' => $request->getUri(),
            'method' => $request->getMethod(),
            'referer' => $request->headers->get('Referer'),
            'timestamp' => new \DateTime()
        ]);
    }

    /**
     * Sprawdza wiele warunków autoryzacji jednocześnie
     */
    public function checkMultipleConditions(array $conditions, ?User $user, Request $request): void
    {
        foreach ($conditions as $condition) {
            match ($condition['type']) {
                'module' => $this->checkModuleAccess($user, $condition['value'], $request),
                'permission' => $this->checkPermission($user, $condition['module'], $condition['permission'], $request),
                'any_permission' => $this->hasAnyPermission($user, $condition['module'], $condition['permissions'], $request),
                'resource_ownership' => $this->checkResourceOwnership(
                    $user, 
                    $condition['resource'], 
                    $condition['owner_property'] ?? 'user',
                    $condition['admin_permission'] ?? 'ADMIN',
                    $request
                ),
                default => throw new \InvalidArgumentException("Unknown condition type: {$condition['type']}")
            };
        }
    }
}