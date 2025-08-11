<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\Role;
use App\Entity\Permission;
use App\Service\AuthorizationService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Psr\Log\LoggerInterface;

class AuthorizationServiceTest extends TestCase
{
    private AuthorizationService $authorizationService;
    private MockObject|LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->authorizationService = new AuthorizationService($this->loggerMock);
    }

    public function testHasPermissionReturnsTrueWhenUserHasPermission(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW', 'EDIT']]);
        
        // Act & Assert
        $this->assertTrue($this->authorizationService->hasPermission($user, 'equipment', 'VIEW'));
        $this->assertTrue($this->authorizationService->hasPermission($user, 'equipment', 'EDIT'));
    }

    public function testHasPermissionReturnsFalseWhenUserDoesNotHavePermission(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        
        // Act & Assert
        $this->assertFalse($this->authorizationService->hasPermission($user, 'equipment', 'DELETE'));
        $this->assertFalse($this->authorizationService->hasPermission($user, 'users', 'VIEW'));
    }

    public function testHasPermissionReturnsFalseForInactiveUser(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $user->setIsActive(false);
        
        // Act & Assert
        $this->assertFalse($this->authorizationService->hasPermission($user, 'equipment', 'VIEW'));
    }

    public function testHasAnyPermissionReturnsTrueWhenUserHasAtLeastOnePermission(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        
        // Act & Assert
        $this->assertTrue($this->authorizationService->hasAnyPermission($user, 'equipment', ['VIEW', 'DELETE']));
    }

    public function testHasAnyPermissionReturnsFalseWhenUserHasNoPermissions(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        
        // Act & Assert
        $this->assertFalse($this->authorizationService->hasAnyPermission($user, 'equipment', ['DELETE', 'CREATE']));
    }

    public function testHasModuleAccessReturnsTrueWhenUserHasAnyPermissionInModule(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        
        // Act & Assert
        $this->assertTrue($this->authorizationService->hasModuleAccess($user, 'equipment'));
    }

    public function testHasModuleAccessReturnsFalseWhenUserHasNoPermissionsInModule(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['users' => ['VIEW']]);
        
        // Act & Assert
        $this->assertFalse($this->authorizationService->hasModuleAccess($user, 'equipment'));
    }

    public function testCheckPermissionThrowsExceptionWhenUserDoesNotHavePermission(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $request = new Request();
        
        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with(
                'Unauthorized access attempt',
                $this->callback(function($context) {
                    return isset($context['user']) && 
                           isset($context['module']) && 
                           isset($context['permission']) &&
                           $context['module'] === 'equipment' &&
                           $context['permission'] === 'DELETE';
                })
            );
        
        // Assert
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Brak uprawnień DELETE w module equipment');
        
        // Act
        $this->authorizationService->checkPermission($user, 'equipment', 'DELETE', $request);
    }

    public function testCheckPermissionDoesNotThrowExceptionWhenUserHasPermission(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['DELETE']]);
        $request = new Request();
        
        // Act - should not throw exception
        $this->authorizationService->checkPermission($user, 'equipment', 'DELETE', $request);
        
        // Assert - if we reach here, no exception was thrown
        $this->assertTrue(true);
    }

    public function testCheckModuleAccessThrowsExceptionWhenUserHasNoModuleAccess(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['users' => ['VIEW']]);
        $request = new Request();
        
        $this->loggerMock->expects($this->once())
            ->method('warning');
        
        // Assert
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Brak dostępu do modułu equipment');
        
        // Act
        $this->authorizationService->checkModuleAccess($user, 'equipment', $request);
    }

    public function testGetUserModulesReturnsModulesUserHasAccessTo(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions([
            'equipment' => ['VIEW', 'EDIT'],
            'users' => ['VIEW'],
            'admin' => ['VIEW']
        ]);
        
        // Act
        $modules = $this->authorizationService->getUserModules($user);
        
        // Assert
        $this->assertCount(3, $modules);
        $moduleNames = array_column($modules, 'name');
        $this->assertContains('equipment', $moduleNames);
        $this->assertContains('users', $moduleNames);
        $this->assertContains('admin', $moduleNames);
    }

    public function testGetUserModulesReturnsEmptyArrayForInactiveUser(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $user->setIsActive(false);
        
        // Act
        $modules = $this->authorizationService->getUserModules($user);
        
        // Assert
        $this->assertEmpty($modules);
    }

    public function testCanEditResourceOwnerCanEditOwnResource(): void
    {
        // Arrange
        $user = $this->createUserWithPermissions(['equipment' => ['EDIT']]);
        $resource = $this->createMockResource($user);
        
        // Act & Assert
        $this->assertTrue($this->authorizationService->canEditResource($user, $resource));
    }

    public function testCanEditResourceOwnerCannotEditOthersResourceWithoutPermission(): void
    {
        // Arrange
        $owner = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $otherUser = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $resource = $this->createMockResource($owner);
        
        // Act & Assert
        $this->assertFalse($this->authorizationService->canEditResource($otherUser, $resource));
    }

    public function testCanEditResourceAdminCanEditAnyResource(): void
    {
        // Arrange
        $owner = $this->createUserWithPermissions(['equipment' => ['VIEW']]);
        $admin = $this->createUserWithPermissions(['equipment' => ['EDIT_ALL']]);
        $resource = $this->createMockResource($owner);
        
        // Act & Assert
        $this->assertTrue($this->authorizationService->canEditResource($admin, $resource));
    }

    private function createUserWithPermissions(array $modulePermissions): User
    {
        $user = new User();
        $user->setUsername('testuser')
             ->setEmail('test@example.com')
             ->setIsActive(true);

        foreach ($modulePermissions as $moduleName => $permissions) {
            $role = new Role();
            $role->setName("ROLE_" . strtoupper($moduleName));
            $role->setModule($moduleName);

            foreach ($permissions as $permissionName) {
                $permission = new Permission();
                $permission->setName($permissionName)
                          ->setModule($moduleName)
                          ->setDescription("Test permission");
                $role->addPermission($permission);
            }

            $user->addRole($role);
        }

        return $user;
    }

    private function createMockResource($owner): object
    {
        return new class($owner) {
            public function __construct(private $owner) {}
            public function getCreatedBy() { return $this->owner; }
            public function getOwner() { return $this->owner; }
        };
    }
}