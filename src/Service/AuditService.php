<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class AuditService
{
    public function __construct(
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    /**
     * Loguje akcję użytkownika z pełnym kontekstem
     */
    public function logUserAction(
        User $user, 
        string $action, 
        array $context = [], 
        ?Request $request = null
    ): void {
        $logContext = [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'action' => $action,
            'timestamp' => new \DateTime(),
            ...$context
        ];

        if ($request) {
            $logContext = array_merge($logContext, [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'referer' => $request->headers->get('Referer')
            ]);
        }

        $this->logger->info("User action: $action", $logContext);
    }

    /**
     * Loguje akcję systemową
     */
    public function logSystemAction(string $action, array $context = []): void
    {
        $logContext = [
            'action' => $action,
            'timestamp' => new \DateTime(),
            'system' => true,
            ...$context
        ];

        $this->logger->info("System action: $action", $logContext);
    }

    /**
     * Loguje operacje CRUD z szczegółami
     */
    public function logCrudOperation(
        User $user,
        string $operation, // create, update, delete, read
        string $entityType,
        $entityId,
        array $changes = [],
        ?Request $request = null
    ): void {
        $this->logUserAction($user, "{$operation}_{$entityType}", [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'operation' => $operation,
            'changes' => $changes,
            'crud_operation' => true
        ], $request);
    }

    /**
     * Loguje akcje administracyjne
     */
    public function logAdminAction(
        User $user,
        string $action,
        array $context = [],
        ?Request $request = null
    ): void {
        $this->logUserAction($user, $action, [
            'admin_action' => true,
            'severity' => 'high',
            ...$context
        ], $request);
    }

    /**
     * Loguje operacje związane z bezpieczeństwem
     */
    public function logSecurityEvent(
        string $event,
        ?User $user = null,
        array $context = [],
        ?Request $request = null
    ): void {
        $logContext = [
            'security_event' => true,
            'event' => $event,
            'timestamp' => new \DateTime(),
            ...$context
        ];

        if ($user) {
            $logContext['user_id'] = $user->getId();
            $logContext['username'] = $user->getUsername();
        }

        if ($request) {
            $logContext = array_merge($logContext, [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'url' => $request->getUri()
            ]);
        }

        $this->logger->warning("Security event: $event", $logContext);
    }

    /**
     * Loguje operacje na plikach
     */
    public function logFileOperation(
        User $user,
        string $operation, // upload, download, delete
        string $filename,
        array $context = [],
        ?Request $request = null
    ): void {
        $this->logUserAction($user, "file_{$operation}", [
            'file_operation' => true,
            'operation' => $operation,
            'filename' => $filename,
            'file_size' => $context['file_size'] ?? null,
            'mime_type' => $context['mime_type'] ?? null,
            ...$context
        ], $request);
    }

    /**
     * Loguje operacje LDAP
     */
    public function logLdapOperation(
        User $user,
        string $operation,
        bool $success,
        array $context = [],
        ?Request $request = null
    ): void {
        $this->logUserAction($user, "ldap_{$operation}", [
            'ldap_operation' => true,
            'operation' => $operation,
            'success' => $success,
            'ldap_server' => $context['server'] ?? null,
            'users_affected' => $context['users_affected'] ?? 0,
            ...$context
        ], $request);
    }

    /**
     * Loguje operacje na bazie danych
     */
    public function logDatabaseOperation(
        User $user,
        string $operation, // backup, restore, optimize, analyze
        bool $success,
        array $context = [],
        ?Request $request = null
    ): void {
        $this->logAdminAction($user, "database_{$operation}", [
            'database_operation' => true,
            'operation' => $operation,
            'success' => $success,
            'duration' => $context['duration'] ?? null,
            'affected_tables' => $context['affected_tables'] ?? null,
            'file_size' => $context['file_size'] ?? null,
            ...$context
        ], $request);
    }

    /**
     * Loguje zmiany w ustawieniach systemu
     */
    public function logSettingChange(
        User $user,
        string $settingKey,
        $oldValue,
        $newValue,
        ?Request $request = null
    ): void {
        $this->logAdminAction($user, 'setting_change', [
            'setting_key' => $settingKey,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'setting_change' => true
        ], $request);
    }

    /**
     * Loguje zmiany w rolach użytkowników
     */
    public function logRoleChange(
        User $admin,
        User $targetUser,
        array $oldRoles,
        array $newRoles,
        ?Request $request = null
    ): void {
        $this->logAdminAction($admin, 'role_change', [
            'target_user_id' => $targetUser->getId(),
            'target_username' => $targetUser->getUsername(),
            'old_roles' => $oldRoles,
            'new_roles' => $newRoles,
            'roles_added' => array_diff($newRoles, $oldRoles),
            'roles_removed' => array_diff($oldRoles, $newRoles),
            'role_change' => true
        ], $request);
    }

    /**
     * Loguje błędy aplikacji
     */
    public function logError(
        string $error,
        array $context = [],
        ?User $user = null,
        ?Request $request = null
    ): void {
        $logContext = [
            'error' => true,
            'message' => $error,
            'timestamp' => new \DateTime(),
            ...$context
        ];

        if ($user) {
            $logContext['user_id'] = $user->getId();
            $logContext['username'] = $user->getUsername();
        }

        if ($request) {
            $logContext = array_merge($logContext, [
                'ip' => $request->getClientIp(),
                'url' => $request->getUri(),
                'method' => $request->getMethod()
            ]);
        }

        $this->logger->error($error, $logContext);
    }

    /**
     * Pobiera logi audytu (mock - w rzeczywistości może być implementacja z bazy danych)
     */
    public function getAuditLogs(array $filters = []): array
    {
        // TODO: Implementacja pobierania logów z bazy danych lub systemu logów
        // Na razie zwracamy pustą tablicę
        return [];
    }

    /**
     * Czyści wrażliwe dane przed logowaniem
     */
    private function sanitizeValue($value): mixed
    {
        if (is_string($value)) {
            // Ukryj hasła i inne wrażliwe dane
            if (str_contains(strtolower($value), 'password') || 
                str_contains(strtolower($value), 'secret') ||
                str_contains(strtolower($value), 'token')) {
                return '***HIDDEN***';
            }
        }

        return $value;
    }
}