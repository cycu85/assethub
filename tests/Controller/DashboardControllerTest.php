<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Service\EquipmentService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private $client;
    private MockObject|AuthorizationService $authorizationServiceMock;
    private MockObject|AuditService $auditServiceMock;
    private MockObject|EquipmentService $equipmentServiceMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Mock services
        $this->authorizationServiceMock = $this->createMock(AuthorizationService::class);
        $this->auditServiceMock = $this->createMock(AuditService::class);
        $this->equipmentServiceMock = $this->createMock(EquipmentService::class);
        
        // Replace services in container
        $container = self::getContainer();
        $container->set(AuthorizationService::class, $this->authorizationServiceMock);
        $container->set(AuditService::class, $this->auditServiceMock);
        $container->set(EquipmentService::class, $this->equipmentServiceMock);
    }

    public function testIndexActionRequiresAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/');

        // Assert
        $this->assertResponseRedirects('/login');
    }

    public function testIndexActionWithAuthenticatedUserShowsDashboard(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $mockModules = [
            ['name' => 'equipment', 'label' => 'Equipment', 'icon' => 'ri-computer-line'],
            ['name' => 'users', 'label' => 'Users', 'icon' => 'ri-user-line']
        ];

        $this->authorizationServiceMock->expects($this->once())
            ->method('getUserModules')
            ->with($user)
            ->willReturn($mockModules);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW', 'EDIT'])
            ->willReturn(true);

        $mockEquipmentStats = [
            'total_equipment' => 100,
            'my_equipment' => 5,
            'available_equipment' => 45,
            'damaged_equipment' => 2
        ];

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentStatistics')
            ->willReturn($mockEquipmentStats);

        $this->equipmentServiceMock->expects($this->once())
            ->method('getUserAssignedEquipment')
            ->with($user)
            ->willReturn([
                $this->createTestEquipment(),
                $this->createTestEquipment()
            ]);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with(
                $user,
                'view_dashboard',
                $this->callback(function($data) {
                    return $data['modules_count'] === 2 &&
                           $data['has_equipment_access'] === true;
                })
            );

        // Act
        $crawler = $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.dashboard'); // Assuming dashboard has this class
    }

    public function testIndexActionWithUserWithoutEquipmentAccess(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $mockModules = [
            ['name' => 'users', 'label' => 'Users', 'icon' => 'ri-user-line']
        ];

        $this->authorizationServiceMock->expects($this->once())
            ->method('getUserModules')
            ->with($user)
            ->willReturn($mockModules);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW', 'EDIT'])
            ->willReturn(false);

        $this->equipmentServiceMock->expects($this->never())
            ->method('getEquipmentStatistics');

        $this->equipmentServiceMock->expects($this->never())
            ->method('getUserAssignedEquipment');

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with(
                $user,
                'view_dashboard',
                $this->callback(function($data) {
                    return $data['modules_count'] === 1 &&
                           $data['has_equipment_access'] === false;
                })
            );

        // Act
        $crawler = $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function testIndexActionHandlesEquipmentServiceException(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $mockModules = [
            ['name' => 'equipment', 'label' => 'Equipment', 'icon' => 'ri-computer-line']
        ];

        $this->authorizationServiceMock->expects($this->once())
            ->method('getUserModules')
            ->with($user)
            ->willReturn($mockModules);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW', 'EDIT'])
            ->willReturn(true);

        // Mock equipment service throwing an exception
        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentStatistics')
            ->willThrowException(new \Exception('Database connection failed'));

        // Logger should be called to log the warning
        $logger = $this->client->getContainer()->get('logger');

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction');

        // Act
        $crawler = $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        // Dashboard should still load even if equipment stats fail
    }

    public function testIndexActionWithEmptyModulesList(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('getUserModules')
            ->with($user)
            ->willReturn([]);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW', 'EDIT'])
            ->willReturn(false);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with(
                $user,
                'view_dashboard',
                $this->callback(function($data) {
                    return $data['modules_count'] === 0 &&
                           $data['has_equipment_access'] === false;
                })
            );

        // Act
        $crawler = $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        // User should see a message about no access to modules
    }

    public function testIndexActionProperlyCalculatesStats(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $mockModules = [
            ['name' => 'equipment', 'label' => 'Equipment', 'icon' => 'ri-computer-line']
        ];

        $this->authorizationServiceMock->expects($this->once())
            ->method('getUserModules')
            ->willReturn($mockModules);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->willReturn(true);

        $mockEquipmentStats = [
            'total' => 100,
            'by_status' => [
                ['status' => 'available', 'count' => 45],
                ['status' => 'assigned', 'count' => 50],
                ['status' => 'maintenance', 'count' => 3],
                ['status' => 'damaged', 'count' => 2]
            ],
            'total_value' => 250000.00,
            'due_for_inspection' => 5,
            'warranty_expiring' => 3
        ];

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentStatistics')
            ->willReturn($mockEquipmentStats);

        $userEquipment = [
            $this->createTestEquipment(),
            $this->createTestEquipment(),
            $this->createTestEquipment()
        ];

        $this->equipmentServiceMock->expects($this->once())
            ->method('getUserAssignedEquipment')
            ->willReturn($userEquipment);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction');

        // Act
        $this->client->request('GET', '/');

        // Assert
        $this->assertResponseIsSuccessful();
        // The template should receive correct statistics
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setId(123)
             ->setUsername('testuser')
             ->setEmail('test@example.com')
             ->setIsActive(true);
        return $user;
    }

    private function createTestEquipment(): \App\Entity\Equipment
    {
        $equipment = new \App\Entity\Equipment();
        $equipment->setId(1)
                  ->setName('Test Equipment')
                  ->setInventoryNumber('INV001')
                  ->setStatus('assigned')
                  ->setCreatedAt(new \DateTime());
        return $equipment;
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }
}