<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\BusinessLogicException;
use App\Exception\DatabaseException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminService
{
    public function __construct(
        private SettingService $settingService,
        private EntityManagerInterface $entityManager,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private KernelInterface $kernel,
        private Security $security,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Resetuje ustawienia ogólne do domyślnych wartości
     */
    public function resetGeneralSettingsToDefaults(User $admin): array
    {
        $defaultSettings = [
            'app_name' => 'AssetHub',
            'app_description' => 'System zarządzania zasobami',
            'app_version' => '1.0.0',
            'maintenance_mode' => 'false',
            'registration_enabled' => 'false',
            'max_login_attempts' => '5',
            'session_timeout' => '3600',
            'backup_retention_days' => '30',
            'log_retention_days' => '90'
        ];

        $resetCount = 0;
        foreach ($defaultSettings as $key => $value) {
            $this->settingService->set($key, $value, 'general');
            $resetCount++;
        }

        $this->auditService->logAdminAction($admin, 'reset_general_settings', [
            'settings_reset' => $resetCount,
            'default_settings' => $defaultSettings
        ]);

        return [
            'success' => true,
            'message' => 'Ustawienia ogólne zostały zresetowane do wartości domyślnych',
            'details' => [
                'reset_count' => $resetCount,
                'settings' => $defaultSettings
            ]
        ];
    }

    /**
     * Pobiera dane dla dashboardu administratora
     */
    public function getDashboardData(): array
    {
        return [
            'system_stats' => [
                'users_count' => $this->entityManager->getRepository('App\\Entity\\User')->count([]),
                'equipment_count' => $this->entityManager->getRepository('App\\Entity\\Equipment')->count([]),
                'active_sessions' => 0, // TODO: Implement session counting
            ],
            'recent_activities' => [], // TODO: Get recent audit logs
            'system_info' => $this->getSystemInfo()
        ];
    }

    /**
     * Pobiera informacje o systemie
     */
    public function getSystemInfo(): array
    {
        $projectDir = $this->kernel->getProjectDir();
        
        return [
            'php' => [
                'version' => PHP_VERSION,
                'extensions' => get_loaded_extensions(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'symfony' => [
                'version' => $this->kernel::VERSION,
                'environment' => $this->kernel->getEnvironment(),
                'debug' => $this->kernel->isDebug()
            ],
            'server' => [
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => php_uname(),
                'load_average' => sys_getloadavg(),
                'disk_space' => [
                    'total' => disk_total_space($projectDir),
                    'free' => disk_free_space($projectDir)
                ]
            ],
            'database' => $this->getDatabaseInfo(),
            'application' => [
                'name' => $this->settingService->get('app_name', 'AssetHub'),
                'version' => $this->settingService->get('app_version', '1.0.0'),
                'installation_date' => $this->settingService->get('installation_date'),
                'last_backup' => $this->settingService->get('last_backup_date'),
                'maintenance_mode' => $this->settingService->get('maintenance_mode', 'false') === 'true'
            ]
        ];
    }

    /**
     * Wykonuje konserwację bazy danych
     */
    public function performDatabaseMaintenance(string $type = 'all'): array
    {
        $startTime = microtime(true);
        $results = [];

        try {
            switch ($type) {
                case 'optimize':
                    $results['optimize'] = $this->optimizeDatabase();
                    break;
                case 'analyze':
                    $results['analyze'] = $this->analyzeDatabase();
                    break;
                case 'cleanup':
                    $results['cleanup'] = $this->cleanupOldLogs();
                    break;
                default:
                    // All maintenance operations
                    $results['optimize'] = $this->optimizeDatabase();
                    $results['analyze'] = $this->analyzeDatabase();
                    $results['cleanup'] = $this->cleanupOldLogs();
                    break;
            }

            $duration = microtime(true) - $startTime;

            return [
                'success' => true,
                'message' => 'Konserwacja bazy danych zakończona pomyślnie',
                'affected_tables' => $results['optimize']['optimized_tables'] ?? $results['analyze']['analyzed_tables'] ?? 0,
                'details' => $results,
                'duration' => round($duration, 2)
            ];

        } catch (\Exception $e) {
            throw new DatabaseException('Konserwacja bazy danych nie powiodła się: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Czyści logi systemowe
     */
    public function clearSystemLogs(array $logTypes = ['application', 'security', 'performance']): array
    {
        $projectDir = $this->kernel->getProjectDir();
        $logDir = $projectDir . '/var/log';
        
        $clearedFiles = [];
        $errors = [];

        foreach ($logTypes as $logType) {
            $pattern = $logDir . '/' . $logType . '*.log';
            $files = glob($pattern);

            foreach ($files as $file) {
                try {
                    if (file_exists($file)) {
                        $size = filesize($file);
                        if (unlink($file)) {
                            $clearedFiles[] = [
                                'file' => basename($file),
                                'size' => $size,
                                'type' => $logType
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Nie można usunąć pliku {$file}: " . $e->getMessage();
                }
            }
        }

        // Audit logging handled in controller

        return [
            'success' => true,
            'message' => 'Czyszczenie logów zakończone',
            'deleted_files' => count($clearedFiles),
            'total_size_freed' => array_sum(array_column($clearedFiles, 'size')),
            'errors' => $errors,
            'cleared_files' => $clearedFiles
        ];
    }

    /**
     * Tworzy kopię zapasową bazy danych
     */
    public function createDatabaseBackup(): string
    {
        try {
            $connection = $this->entityManager->getConnection();
            $databaseName = $connection->getDatabase();
            
            if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
                throw new DatabaseException('Backup jest obsługiwany tylko dla MySQL');
            }

            $backupDir = $this->kernel->getProjectDir() . '/var/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = 'backup_' . $databaseName . '_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;

            // Pobierz parametry połączenia
            $params = $connection->getParams();
            $host = $params['host'] ?? 'localhost';
            $port = $params['port'] ?? 3306;
            $username = $params['user'] ?? '';
            $password = $params['password'] ?? '';

            // Utwórz komendę mysqldump
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s --single-transaction --routines --triggers %s > %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($username),
                escapeshellarg($password),
                escapeshellarg($databaseName),
                escapeshellarg($filepath)
            );

            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($filepath)) {
                throw new DatabaseException('Backup nie powiódł się');
            }

            $fileSize = filesize($filepath);
            $this->settingService->set('last_backup_date', (new \DateTime())->format('Y-m-d H:i:s'));

            return $filename;

        } catch (\Exception $e) {
            throw new DatabaseException('Tworzenie kopii zapasowej nie powiodło się: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Pobiera listę kopii zapasowych
     */
    public function listBackups(): array
    {
        $backupDir = $this->kernel->getProjectDir() . '/var/backups';
        
        if (!is_dir($backupDir)) {
            return [];
        }

        $backups = [];
        $files = glob($backupDir . '/backup_*.sql');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'file_path' => $file,
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                'age_days' => floor((time() - filemtime($file)) / 86400)
            ];
        }

        // Sortuj po dacie utworzenia (najnowsze pierwsze)
        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Usuwa stare kopie zapasowe
     */
    public function cleanupOldBackups(int $daysToKeep = null, User $admin = null): array
    {
        $daysToKeep = $daysToKeep ?? (int) $this->settingService->get('backup_retention_days', '30');
        $cutoffTimestamp = time() - ($daysToKeep * 86400);
        
        $backups = $this->listBackups();
        $deletedBackups = [];
        $errors = [];

        foreach ($backups as $backup) {
            if (filemtime($backup['file_path']) < $cutoffTimestamp) {
                try {
                    if (unlink($backup['file_path'])) {
                        $deletedBackups[] = $backup;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Nie można usunąć {$backup['filename']}: " . $e->getMessage();
                }
            }
        }

        if ($admin) {
            $this->auditService->logAdminAction($admin, 'cleanup_old_backups', [
                'days_to_keep' => $daysToKeep,
                'deleted_count' => count($deletedBackups),
                'errors' => count($errors)
            ]);
        }

        return [
            'success' => true,
            'message' => 'Czyszczenie starych kopii zapasowych zakończone',
            'details' => [
                'days_to_keep' => $daysToKeep,
                'deleted_count' => count($deletedBackups),
                'freed_space' => array_sum(array_column($deletedBackups, 'size')),
                'errors' => $errors,
                'deleted_backups' => $deletedBackups
            ]
        ];
    }

    /**
     * Synchronizuje użytkowników z LDAP
     */
    public function syncLdapUsers(User $admin, string $syncType = 'existing'): array
    {
        $ldapSettings = [
            'ldap_host' => $this->settingService->get('ldap_host'),
            'ldap_port' => $this->settingService->get('ldap_port', '389'),
            'ldap_encryption' => $this->settingService->get('ldap_encryption', 'none'),
            'ldap_base_dn' => $this->settingService->get('ldap_base_dn'),
            'ldap_search_dn' => $this->settingService->get('ldap_search_dn'),
            'ldap_search_password' => $this->settingService->get('ldap_search_password'),
            'ldap_user_filter' => $this->settingService->get('ldap_user_filter', '(objectClass=person)'),
            'ldap_username_attribute' => $this->settingService->get('ldap_username_attribute', 'uid')
        ];

        try {
            $result = match ($syncType) {
                'existing' => $this->ldapService->syncExistingUsers($ldapSettings),
                'new' => $this->ldapService->syncNewUsers($ldapSettings),
                'hierarchy' => $this->ldapService->syncManagerHierarchy($ldapSettings),
                default => throw new BusinessLogicException("Nieznany typ synchronizacji: {$syncType}")
            };

            $this->auditService->logLdapOperation($admin, "sync_{$syncType}_users", true, [
                'sync_type' => $syncType,
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->auditService->logLdapOperation($admin, "sync_{$syncType}_users", false, [
                'sync_type' => $syncType,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Testuje połączenie LDAP
     */
    public function testLdapConnection(User $admin): array
    {
        $ldapSettings = [
            'ldap_host' => $this->settingService->get('ldap_host'),
            'ldap_port' => $this->settingService->get('ldap_port', '389'),
            'ldap_encryption' => $this->settingService->get('ldap_encryption', 'none'),
            'ldap_base_dn' => $this->settingService->get('ldap_base_dn'),
            'ldap_search_dn' => $this->settingService->get('ldap_search_dn'),
            'ldap_search_password' => $this->settingService->get('ldap_search_password'),
            'ldap_user_filter' => $this->settingService->get('ldap_user_filter', '(objectClass=person)')
        ];

        try {
            $result = $this->ldapService->testConnection($ldapSettings);

            $this->auditService->logLdapOperation($admin, 'test_connection', true, [
                'server' => $ldapSettings['ldap_host'],
                'result' => $result
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->auditService->logLdapOperation($admin, 'test_connection', false, [
                'server' => $ldapSettings['ldap_host'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Pobiera informacje o bazie danych
     */
    public function getDatabaseInfo(): array
    {
        $connection = $this->entityManager->getConnection();
        
        try {
            $platform = $connection->getDatabasePlatform();
            $info = [
                'platform' => $this->getPlatformName($platform),
                'version' => $connection->fetchOne('SELECT VERSION()'),
                'database_name' => $connection->getDatabase()
            ];

            // Statystyki tabel (tylko dla MySQL)
            if ($this->isMySQLPlatform($connection->getDatabasePlatform())) {
                $tablesInfo = $connection->fetchAllAssociative('SHOW TABLE STATUS');
                $totalSize = 0;
                $totalRows = 0;

                foreach ($tablesInfo as $table) {
                    $totalSize += $table['Data_length'] + $table['Index_length'];
                    $totalRows += $table['Rows'];
                }

                $info['total_size'] = $totalSize;
                $info['total_rows'] = $totalRows;
                $info['tables_count'] = count($tablesInfo);
            }

            return $info;

        } catch (\Exception $e) {
            return [
                'platform' => 'unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Optymalizuje bazę danych
     */
    private function optimizeDatabase(): array
    {
        $connection = $this->entityManager->getConnection();
        
        if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
            throw new DatabaseException('Optymalizacja jest obsługiwana tylko dla MySQL');
        }

        $tablesResult = $connection->fetchAllAssociative('SHOW TABLES');
        $tables = array_map(fn($row) => array_values($row)[0], $tablesResult);
        
        $optimized = 0;
        
        foreach ($tables as $table) {
            try {
                // Zabezpieczenie przed SQL injection - walidacja nazwy tabeli
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    continue;
                }
                $connection->executeStatement("OPTIMIZE TABLE `{$table}`");
                $optimized++;
            } catch (\Exception $e) {
                // Loguj błąd ale kontynuuj
                $this->logger->warning('Failed to optimize table', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'optimized_tables' => $optimized,
            'total_tables' => count($tables)
        ];
    }

    /**
     * Analizuje bazę danych
     */
    private function analyzeDatabase(): array
    {
        $connection = $this->entityManager->getConnection();
        
        if (!$this->isMySQLPlatform($connection->getDatabasePlatform())) {
            throw new DatabaseException('Analiza jest obsługiwana tylko dla MySQL');
        }

        $tablesResult = $connection->fetchAllAssociative('SHOW TABLES');
        $tables = array_map(fn($row) => array_values($row)[0], $tablesResult);
        
        $analyzed = 0;
        
        foreach ($tables as $table) {
            try {
                // Zabezpieczenie przed SQL injection - walidacja nazwy tabeli
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    continue;
                }
                $connection->executeStatement("ANALYZE TABLE `{$table}`");
                $analyzed++;
            } catch (\Exception $e) {
                // Loguj błąd ale kontynuuj
                $this->logger->warning('Failed to analyze table', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'analyzed_tables' => $analyzed,
            'total_tables' => count($tables)
        ];
    }

    /**
     * Czyści stare logi z bazy danych
     */
    private function cleanupOldLogs(): array
    {
        $retentionDays = (int) $this->settingService->get('log_retention_days', '90');
        $cutoffDate = new \DateTime("-{$retentionDays} days");
        
        $deletedCount = 0;
        
        // Przykład dla tabeli equipment_log
        try {
            $qb = $this->entityManager->createQueryBuilder();
            $deletedCount += $qb->delete('App:EquipmentLog', 'el')
                ->where('el.logDate < :cutoff')
                ->setParameter('cutoff', $cutoffDate)
                ->getQuery()
                ->execute();
        } catch (\Exception $e) {
            // Tabela może nie istnieć
        }

        return [
            'deleted_logs' => $deletedCount,
            'retention_days' => $retentionDays,
            'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Pobiera czytelną nazwę platformy bazy danych
     */
    private function getPlatformName($platform): string
    {
        $className = get_class($platform);
        
        // Mapowanie nazw klas na czytelne nazwy
        $platformNames = [
            'Doctrine\DBAL\Platforms\MySQLPlatform' => 'MySQL',
            'Doctrine\DBAL\Platforms\PostgreSQLPlatform' => 'PostgreSQL',
            'Doctrine\DBAL\Platforms\SqlitePlatform' => 'SQLite',
            'Doctrine\DBAL\Platforms\SQLServerPlatform' => 'SQL Server',
            'Doctrine\DBAL\Platforms\OraclePlatform' => 'Oracle',
        ];
        
        return $platformNames[$className] ?? $className;
    }

    /**
     * Sprawdza czy platforma to MySQL
     */
    private function isMySQLPlatform($platform): bool
    {
        return str_contains(get_class($platform), 'MySQL');
    }
}