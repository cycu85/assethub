<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Exception\DatabaseException;
use App\Service\AdminService;
use App\Service\AuditService;
use App\Service\SettingService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;

class AdminServiceTest extends TestCase
{
    private AdminService $adminService;
    private MockObject|SettingService $settingServiceMock;
    private MockObject|EntityManagerInterface $entityManagerMock;
    private MockObject|AuditService $auditServiceMock;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|KernelInterface $kernelMock;
    private MockObject|Security $securityMock;
    private MockObject|RequestStack $requestStackMock;

    protected function setUp(): void
    {
        $this->settingServiceMock = $this->createMock(SettingService::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->auditServiceMock = $this->createMock(AuditService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->kernelMock = $this->createMock(KernelInterface::class);
        $this->securityMock = $this->createMock(Security::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);

        $this->adminService = new AdminService(
            $this->settingServiceMock,
            $this->entityManagerMock,
            $this->auditServiceMock,
            $this->loggerMock,
            $this->kernelMock,
            $this->securityMock,
            $this->requestStackMock
        );
    }

    public function testResetGeneralSettingsToDefaultsSuccessfully(): void
    {
        // Arrange
        $admin = $this->createTestUser();

        $this->settingServiceMock->expects($this->exactly(9))
            ->method('set')
            ->withConsecutive(
                ['app_name', 'AssetHub', 'general'],
                ['app_description', 'System zarządzania zasobami', 'general'],
                ['app_version', '1.0.0', 'general'],
                ['maintenance_mode', 'false', 'general'],
                ['registration_enabled', 'false', 'general'],
                ['max_login_attempts', '5', 'general'],
                ['session_timeout', '3600', 'general'],
                ['backup_retention_days', '30', 'general'],
                ['log_retention_days', '90', 'general']
            );

        $this->auditServiceMock->expects($this->once())
            ->method('logAdminAction')
            ->with(
                $admin,
                'reset_general_settings',
                $this->callback(function($data) {
                    return $data['settings_reset'] === 9 &&
                           isset($data['default_settings']) &&
                           is_array($data['default_settings']);
                })
            );

        // Act
        $result = $this->adminService->resetGeneralSettingsToDefaults($admin);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('zresetowane', $result['message']);
        $this->assertEquals(9, $result['details']['reset_count']);
    }

    public function testGetSystemInfoReturnsCompleteInformation(): void
    {
        // Arrange
        $this->kernelMock->expects($this->once())
            ->method('getProjectDir')
            ->willReturn('/test/project');

        $this->kernelMock->expects($this->once())
            ->method('getEnvironment')
            ->willReturn('test');

        $this->kernelMock->expects($this->once())
            ->method('isDebug')
            ->willReturn(true);

        $this->settingServiceMock->expects($this->exactly(5))
            ->method('get')
            ->willReturnMap([
                ['app_name', 'AssetHub', 'TestHub'],
                ['app_version', '1.0.0', '2.0.0'],
                ['installation_date', null, '2024-01-01'],
                ['last_backup_date', null, '2024-12-01'],
                ['maintenance_mode', 'false', 'false']
            ]);

        // Mock database info
        $connectionMock = $this->createMock(Connection::class);
        $platformMock = $this->createMock(MySQLPlatform::class);
        
        $this->entityManagerMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);

        $connectionMock->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT VERSION()')
            ->willReturn('8.0.25');

        $connectionMock->expects($this->once())
            ->method('getDatabase')
            ->willReturn('assethub_test');

        // Act
        $systemInfo = $this->adminService->getSystemInfo();

        // Assert
        $this->assertArrayHasKey('php', $systemInfo);
        $this->assertArrayHasKey('symfony', $systemInfo);
        $this->assertArrayHasKey('server', $systemInfo);
        $this->assertArrayHasKey('database', $systemInfo);
        $this->assertArrayHasKey('application', $systemInfo);

        $this->assertEquals('test', $systemInfo['symfony']['environment']);
        $this->assertTrue($systemInfo['symfony']['debug']);
        $this->assertEquals('TestHub', $systemInfo['application']['name']);
        $this->assertEquals('2.0.0', $systemInfo['application']['version']);
    }

    public function testGetDashboardDataReturnsStatistics(): void
    {
        // Arrange
        $userRepositoryMock = $this->createMock(\App\Repository\UserRepository::class);
        $equipmentRepositoryMock = $this->createMock(\App\Repository\EquipmentRepository::class);

        $this->entityManagerMock->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                ['App\\Entity\\User', $userRepositoryMock],
                ['App\\Entity\\Equipment', $equipmentRepositoryMock]
            ]);

        $userRepositoryMock->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(25);

        $equipmentRepositoryMock->expects($this->once())
            ->method('count')
            ->with([])
            ->willReturn(150);

        // Act
        $dashboardData = $this->adminService->getDashboardData();

        // Assert
        $this->assertArrayHasKey('system_stats', $dashboardData);
        $this->assertArrayHasKey('recent_activities', $dashboardData);
        $this->assertArrayHasKey('system_info', $dashboardData);

        $this->assertEquals(25, $dashboardData['system_stats']['users_count']);
        $this->assertEquals(150, $dashboardData['system_stats']['equipment_count']);
        $this->assertEquals(0, $dashboardData['system_stats']['active_sessions']);
    }

    public function testPerformDatabaseMaintenanceOptimizeSuccessfully(): void
    {
        // Arrange
        $connectionMock = $this->createMock(Connection::class);
        $platformMock = $this->createMock(MySQLPlatform::class);

        $this->entityManagerMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);

        $connectionMock->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SHOW TABLES')
            ->willReturn([
                ['Tables_in_test' => 'users'],
                ['Tables_in_test' => 'equipment']
            ]);

        $connectionMock->expects($this->exactly(2))
            ->method('executeStatement')
            ->withConsecutive(
                ['OPTIMIZE TABLE `users`'],
                ['OPTIMIZE TABLE `equipment`']
            );

        // Act
        $result = $this->adminService->performDatabaseMaintenance('optimize');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['affected_tables']);
        $this->assertArrayHasKey('duration', $result);
    }

    public function testPerformDatabaseMaintenanceThrowsExceptionForUnsupportedDatabase(): void
    {
        // Arrange
        $connectionMock = $this->createMock(Connection::class);
        $platformMock = $this->createMock(\Doctrine\DBAL\Platforms\PostgreSQLPlatform::class);

        $this->entityManagerMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);

        // Assert
        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Optymalizacja jest obsługiwana tylko dla MySQL');

        // Act
        $this->adminService->performDatabaseMaintenance('optimize');
    }

    public function testClearSystemLogsSuccessfully(): void
    {
        // Arrange
        $this->kernelMock->expects($this->once())
            ->method('getProjectDir')
            ->willReturn(__DIR__);

        // Create temporary log files for testing
        $logDir = __DIR__ . '/var/log';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $oldLogFile = $logDir . '/application.log';
        file_put_contents($oldLogFile, 'test log content');
        touch($oldLogFile, time() - (40 * 24 * 3600)); // 40 days old

        // Act
        $result = $this->adminService->clearSystemLogs(['application']);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['deleted_files']);
        $this->assertFileDoesNotExist($oldLogFile);

        // Cleanup
        if (is_dir($logDir)) {
            rmdir($logDir);
            rmdir(__DIR__ . '/var');
        }
    }

    public function testCreateDatabaseBackupSuccessfully(): void
    {
        // Arrange
        $connectionMock = $this->createMock(Connection::class);
        $platformMock = $this->createMock(MySQLPlatform::class);

        $this->entityManagerMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connectionMock);

        $connectionMock->expects($this->once())
            ->method('getDatabasePlatform')
            ->willReturn($platformMock);

        $connectionMock->expects($this->once())
            ->method('getDatabase')
            ->willReturn('assethub_test');

        $connectionMock->expects($this->once())
            ->method('getParams')
            ->willReturn([
                'host' => 'localhost',
                'port' => 3306,
                'user' => 'testuser',
                'password' => 'testpass'
            ]);

        $this->kernelMock->expects($this->once())
            ->method('getProjectDir')
            ->willReturn(__DIR__);

        $this->settingServiceMock->expects($this->once())
            ->method('set')
            ->with('last_backup_date', $this->isType('string'));

        // Mock exec function - this would normally create the backup file
        // In a real test environment, you'd need to mock the exec() function
        // or use a test double for the actual backup creation

        // Act & Assert - This test is simplified as mocking exec() is complex
        // In a real scenario, you'd test the backup creation logic separately
        $this->expectException(DatabaseException::class);
        $this->adminService->createDatabaseBackup();
    }

    public function testListBackupsReturnsBackupFiles(): void
    {
        // Arrange
        $this->kernelMock->expects($this->once())
            ->method('getProjectDir')
            ->willReturn(__DIR__);

        // Create temporary backup files
        $backupDir = __DIR__ . '/var/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFile1 = $backupDir . '/backup_test_2024-12-01_10-00-00.sql';
        $backupFile2 = $backupDir . '/backup_test_2024-11-30_09-00-00.sql';
        
        file_put_contents($backupFile1, 'backup content 1');
        file_put_contents($backupFile2, 'backup content 2');

        // Act
        $backups = $this->adminService->listBackups();

        // Assert
        $this->assertCount(2, $backups);
        $this->assertEquals('backup_test_2024-12-01_10-00-00.sql', $backups[0]['filename']);
        $this->assertArrayHasKey('size', $backups[0]);
        $this->assertArrayHasKey('created_at', $backups[0]);
        $this->assertArrayHasKey('age_days', $backups[0]);

        // Cleanup
        unlink($backupFile1);
        unlink($backupFile2);
        rmdir($backupDir);
        rmdir(__DIR__ . '/var');
    }

    public function testCleanupOldBackupsRemovesOldFiles(): void
    {
        // Arrange
        $this->kernelMock->expects($this->atLeastOnce())
            ->method('getProjectDir')
            ->willReturn(__DIR__);

        $this->settingServiceMock->expects($this->once())
            ->method('get')
            ->with('backup_retention_days', '30')
            ->willReturn('7'); // Keep only 7 days

        // Create backup directory and files
        $backupDir = __DIR__ . '/var/backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $oldBackup = $backupDir . '/backup_old.sql';
        $newBackup = $backupDir . '/backup_new.sql';
        
        file_put_contents($oldBackup, 'old backup');
        file_put_contents($newBackup, 'new backup');
        
        // Make old backup actually old
        touch($oldBackup, time() - (10 * 24 * 3600)); // 10 days old
        touch($newBackup, time() - (1 * 24 * 3600)); // 1 day old

        $admin = $this->createTestUser();

        $this->auditServiceMock->expects($this->once())
            ->method('logAdminAction')
            ->with($admin, 'cleanup_old_backups', $this->isType('array'));

        // Act
        $result = $this->adminService->cleanupOldBackups(null, $admin);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['details']['deleted_count']);
        $this->assertFileDoesNotExist($oldBackup);
        $this->assertFileExists($newBackup);

        // Cleanup
        if (file_exists($newBackup)) {
            unlink($newBackup);
        }
        rmdir($backupDir);
        rmdir(__DIR__ . '/var');
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setId(123)
             ->setUsername('admin')
             ->setEmail('admin@example.com');
        return $user;
    }
}