<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

class AuditServiceTest extends TestCase
{
    private AuditService $auditService;
    private MockObject|EntityManagerInterface $entityManagerMock;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|RequestStack $requestStackMock;

    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);

        $this->auditService = new AuditService(
            $this->entityManagerMock,
            $this->loggerMock,
            $this->requestStackMock
        );
    }

    public function testLogUserActionLogsWithCorrectData(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $action = 'view_equipment';
        $data = ['equipment_id' => 123];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'User action: view_equipment',
                $this->callback(function($context) use ($user, $data) {
                    return $context['user_id'] === $user->getId() &&
                           $context['username'] === $user->getUsername() &&
                           $context['action'] === 'view_equipment' &&
                           $context['data'] === $data &&
                           isset($context['ip_address']) &&
                           isset($context['user_agent']) &&
                           isset($context['timestamp']);
                })
            );

        // Act
        $this->auditService->logUserAction($user, $action, $data, $request);
    }

    public function testLogUserActionWithoutRequestUsesRequestStack(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $action = 'test_action';
        $data = [];
        $request = $this->createTestRequest();

        $this->requestStackMock->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->loggerMock->expects($this->once())
            ->method('info');

        // Act
        $this->auditService->logUserAction($user, $action, $data);
    }

    public function testLogSecurityEventLogsAsWarning(): void
    {
        // Arrange
        $eventType = 'failed_login';
        $user = $this->createTestUser();
        $data = ['attempts' => 3];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Security event: failed_login',
                $this->callback(function($context) use ($user, $data) {
                    return $context['event_type'] === 'failed_login' &&
                           $context['user_id'] === $user->getId() &&
                           $context['username'] === $user->getUsername() &&
                           $context['data'] === $data &&
                           isset($context['ip_address']) &&
                           isset($context['severity']) &&
                           $context['severity'] === 'medium';
                })
            );

        // Act
        $this->auditService->logSecurityEvent($eventType, $user, $data, $request);
    }

    public function testLogSecurityEventWithHighSeverity(): void
    {
        // Arrange
        $eventType = 'admin_account_lockout';
        $user = $this->createTestUser();
        $data = [];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Security event: admin_account_lockout',
                $this->callback(function($context) {
                    return $context['severity'] === 'high';
                })
            );

        // Act
        $this->auditService->logSecurityEvent($eventType, $user, $data, $request, 'high');
    }

    public function testLogCrudOperationForCreateOperation(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $entity = 'Equipment';
        $entityId = 123;
        $operation = 'CREATE';
        $changes = ['name' => 'New Equipment'];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'CRUD operation: CREATE Equipment',
                $this->callback(function($context) use ($user, $entity, $entityId, $changes) {
                    return $context['user_id'] === $user->getId() &&
                           $context['entity'] === $entity &&
                           $context['entity_id'] === $entityId &&
                           $context['operation'] === 'CREATE' &&
                           $context['changes'] === $changes;
                })
            );

        // Act
        $this->auditService->logCrudOperation($user, $entity, $entityId, $operation, $changes, $request);
    }

    public function testLogCrudOperationForDeleteOperation(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $entity = 'User';
        $entityId = 456;
        $operation = 'DELETE';
        $changes = [];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'CRUD operation: DELETE User',
                $this->callback(function($context) {
                    return $context['operation'] === 'DELETE';
                })
            );

        // Act
        $this->auditService->logCrudOperation($user, $entity, $entityId, $operation, $changes, $request);
    }

    public function testLogAdminActionLogsAsNotice(): void
    {
        // Arrange
        $admin = $this->createTestUser();
        $action = 'database_backup';
        $data = ['backup_file' => 'backup_20241201.sql'];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                'Admin action: database_backup',
                $this->callback(function($context) use ($admin, $data) {
                    return $context['admin_id'] === $admin->getId() &&
                           $context['admin_username'] === $admin->getUsername() &&
                           $context['action'] === 'database_backup' &&
                           $context['data'] === $data;
                })
            );

        // Act
        $this->auditService->logAdminAction($admin, $action, $data, $request);
    }

    public function testLogLdapOperationForSuccessfulOperation(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $operation = 'sync_users';
        $success = true;
        $data = ['synchronized' => 10];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with(
                'LDAP operation: sync_users - SUCCESS',
                $this->callback(function($context) use ($user, $data) {
                    return $context['user_id'] === $user->getId() &&
                           $context['operation'] === 'sync_users' &&
                           $context['success'] === true &&
                           $context['data'] === $data;
                })
            );

        // Act
        $this->auditService->logLdapOperation($user, $operation, $success, $data, $request);
    }

    public function testLogLdapOperationForFailedOperation(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $operation = 'test_connection';
        $success = false;
        $data = ['error' => 'Connection timeout'];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'LDAP operation: test_connection - FAILED',
                $this->callback(function($context) {
                    return $context['success'] === false;
                })
            );

        // Act
        $this->auditService->logLdapOperation($user, $operation, $success, $data, $request);
    }

    public function testLogDatabaseOperationForSuccessfulBackup(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $operation = 'backup';
        $success = true;
        $data = ['filename' => 'backup_20241201.sql', 'size' => 1024000];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('notice')
            ->with(
                'Database operation: backup - SUCCESS',
                $this->callback(function($context) use ($user, $data) {
                    return $context['user_id'] === $user->getId() &&
                           $context['operation'] === 'backup' &&
                           $context['success'] === true &&
                           $context['data'] === $data;
                })
            );

        // Act
        $this->auditService->logDatabaseOperation($user, $operation, $success, $data, $request);
    }

    public function testLogDatabaseOperationForFailedMaintenance(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $operation = 'optimize';
        $success = false;
        $data = ['error' => 'Table lock timeout'];
        $request = $this->createTestRequest();

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Database operation: optimize - FAILED',
                $this->callback(function($context) {
                    return $context['success'] === false;
                })
            );

        // Act
        $this->auditService->logDatabaseOperation($user, $operation, $success, $data, $request);
    }

    public function testGetRequestContextReturnsCorrectData(): void
    {
        // Arrange
        $request = $this->createTestRequest();

        // Act
        $context = $this->auditService->getRequestContext($request);

        // Assert
        $this->assertEquals('192.168.1.1', $context['ip_address']);
        $this->assertEquals('Mozilla/5.0 (Test Browser)', $context['user_agent']);
        $this->assertEquals('GET', $context['method']);
        $this->assertEquals('/test', $context['uri']);
        $this->assertInstanceOf(\DateTime::class, $context['timestamp']);
    }

    public function testGetRequestContextHandlesNullRequest(): void
    {
        // Act
        $context = $this->auditService->getRequestContext(null);

        // Assert
        $this->assertEquals('CLI', $context['ip_address']);
        $this->assertEquals('CLI', $context['user_agent']);
        $this->assertEquals('CLI', $context['method']);
        $this->assertEquals('CLI', $context['uri']);
        $this->assertInstanceOf(\DateTime::class, $context['timestamp']);
    }

    public function testSanitizeDataRemovesSensitiveInformation(): void
    {
        // Arrange
        $data = [
            'username' => 'testuser',
            'password' => 'secret123',
            'token' => 'abc123def456',
            'api_key' => 'key_12345',
            'safe_data' => 'this is fine'
        ];

        // Act
        $sanitized = $this->auditService->sanitizeData($data);

        // Assert
        $this->assertEquals('testuser', $sanitized['username']);
        $this->assertEquals('***', $sanitized['password']);
        $this->assertEquals('***', $sanitized['token']);
        $this->assertEquals('***', $sanitized['api_key']);
        $this->assertEquals('this is fine', $sanitized['safe_data']);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setId(123)
             ->setUsername('testuser')
             ->setEmail('test@example.com');
        return $user;
    }

    private function createTestRequest(): Request
    {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->server->set('HTTP_USER_AGENT', 'Mozilla/5.0 (Test Browser)');
        $request->server->set('REQUEST_METHOD', 'GET');
        $request->server->set('REQUEST_URI', '/test');
        return $request;
    }
}