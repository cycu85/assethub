<?php

namespace App\Tests\Controller;

use App\Entity\Equipment;
use App\Entity\User;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Service\EquipmentService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EquipmentControllerTest extends WebTestCase
{
    private $client;
    private MockObject|AuthorizationService $authorizationServiceMock;
    private MockObject|AuditService $auditServiceMock;
    private MockObject|EquipmentService $equipmentServiceMock;
    private MockObject|EventDispatcherInterface $eventDispatcherMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Mock services
        $this->authorizationServiceMock = $this->createMock(AuthorizationService::class);
        $this->auditServiceMock = $this->createMock(AuditService::class);
        $this->equipmentServiceMock = $this->createMock(EquipmentService::class);
        $this->eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        
        // Replace services in container
        $container = self::getContainer();
        $container->set(AuthorizationService::class, $this->authorizationServiceMock);
        $container->set(AuditService::class, $this->auditServiceMock);
        $container->set(EquipmentService::class, $this->equipmentServiceMock);
        $container->set(EventDispatcherInterface::class, $this->eventDispatcherMock);
    }

    public function testIndexActionRequiresAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/equipment/');

        // Assert
        $this->assertResponseRedirects('/login');
    }

    public function testIndexActionWithAuthenticatedUserShowsEquipmentList(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $mockPagination = $this->createMock(\Knp\Component\Pager\Pagination\PaginationInterface::class);
        $mockPagination->method('getTotalItemCount')->willReturn(25);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkModuleAccess')
            ->with($user, 'equipment');

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentWithPagination')
            ->willReturn($mockPagination);

        $this->equipmentServiceMock->expects($this->once())
            ->method('getActiveCategories')
            ->willReturn([]);

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentStatistics')
            ->willReturn(['total' => 25]);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with($user, 'view_equipment_index');

        $this->authorizationServiceMock->expects($this->exactly(3))
            ->method('hasPermission')
            ->willReturn(true);

        // Act
        $crawler = $this->client->request('GET', '/equipment/');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1'); // Assuming there's an h1 on the page
    }

    public function testNewActionDisplaysForm(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'CREATE');

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with($user, 'access_equipment_new_form');

        // Act
        $crawler = $this->client->request('GET', '/equipment/new');

        // Assert
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testNewActionCreatesEquipmentSuccessfully(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);
        $equipment = $this->createTestEquipment();

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'CREATE');

        $this->equipmentServiceMock->expects($this->once())
            ->method('createEquipment')
            ->willReturn($equipment);

        $this->eventDispatcherMock->expects($this->once())
            ->method('dispatch');

        // Act
        $crawler = $this->client->request('GET', '/equipment/new');
        $form = $crawler->selectButton('Save')->form([
            'equipment[name]' => 'Test Equipment',
            'equipment[description]' => 'Test Description',
            'equipment[inventoryNumber]' => 'INV001',
            'equipment[status]' => 'available'
        ]);

        $this->client->submit($form);

        // Assert
        $this->assertResponseRedirects('/equipment/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'pomyślnie');
    }

    public function testShowActionDisplaysEquipment(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $equipment = $this->createTestEquipment();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'VIEW');

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with($user, 'view_equipment');

        $this->authorizationServiceMock->expects($this->exactly(2))
            ->method('hasPermission')
            ->willReturn(true);

        // Act
        $this->client->request('GET', '/equipment/1');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function testEditActionUpdatesEquipmentSuccessfully(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $equipment = $this->createTestEquipment();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'EDIT');

        $this->equipmentServiceMock->expects($this->once())
            ->method('updateEquipment')
            ->willReturn($equipment);

        // Act
        $crawler = $this->client->request('GET', '/equipment/1/edit');
        $form = $crawler->selectButton('Update')->form([
            'equipment[name]' => 'Updated Equipment',
            'equipment[status]' => 'maintenance'
        ]);

        $this->client->submit($form);

        // Assert
        $this->assertResponseRedirects('/equipment/1');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'zaktualizowany');
    }

    public function testDeleteActionRemovesEquipmentWithValidCsrfToken(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'DELETE');

        $this->equipmentServiceMock->expects($this->once())
            ->method('deleteEquipment');

        // Generate CSRF token
        $csrfToken = $this->client->getContainer()
            ->get('security.csrf.token_manager')
            ->getToken('delete1')
            ->getValue();

        // Act
        $this->client->request('POST', '/equipment/1/delete', [
            '_token' => $csrfToken
        ]);

        // Assert
        $this->assertResponseRedirects('/equipment/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert-success', 'usunięty');
    }

    public function testDeleteActionRejectsInvalidCsrfToken(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkPermission')
            ->with($user, 'equipment', 'DELETE');

        $this->equipmentServiceMock->expects($this->never())
            ->method('deleteEquipment');

        $this->auditServiceMock->expects($this->once())
            ->method('logSecurityEvent')
            ->with('invalid_csrf_token_delete_equipment');

        // Act
        $this->client->request('POST', '/equipment/1/delete', [
            '_token' => 'invalid_token'
        ]);

        // Assert
        $this->assertResponseRedirects('/equipment/');
    }

    public function testByCategoryActionShowsEquipmentByCategory(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $category = new \App\Entity\EquipmentCategory();
        $category->setName('Test Category');

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkModuleAccess')
            ->with($user, 'equipment');

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentByCategory')
            ->with(1)
            ->willReturn([
                'category' => $category,
                'equipment' => [$this->createTestEquipment()]
            ]);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with($user, 'view_equipment_by_category');

        $this->authorizationServiceMock->expects($this->exactly(3))
            ->method('hasPermission')
            ->willReturn(true);

        // Act
        $this->client->request('GET', '/equipment/category/1');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function testByCategoryActionThrowsNotFoundForInvalidCategory(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkModuleAccess')
            ->with($user, 'equipment');

        $this->equipmentServiceMock->expects($this->once())
            ->method('getEquipmentByCategory')
            ->with(999)
            ->willReturn(['category' => null, 'equipment' => []]);

        // Act & Assert
        $this->client->request('GET', '/equipment/category/999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testMyEquipmentActionShowsUserAssignedEquipment(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkModuleAccess')
            ->with($user, 'equipment');

        $this->equipmentServiceMock->expects($this->once())
            ->method('getUserAssignedEquipment')
            ->with($user)
            ->willReturn([$this->createTestEquipment()]);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with($user, 'view_my_equipment');

        // Act
        $this->client->request('GET', '/equipment/my');

        // Assert
        $this->assertResponseIsSuccessful();
    }

    public function testAccessDeniedWhenUserLacksPermissions(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        $this->authorizationServiceMock->expects($this->once())
            ->method('checkModuleAccess')
            ->willThrowException(new \Symfony\Component\Security\Core\Exception\AccessDeniedException());

        // Act & Assert
        $this->client->request('GET', '/equipment/');
        $this->assertResponseStatusCodeSame(403);
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

    private function createTestEquipment(): Equipment
    {
        $equipment = new Equipment();
        $equipment->setId(1)
                  ->setName('Test Equipment')
                  ->setInventoryNumber('INV001')
                  ->setStatus('available')
                  ->setCreatedAt(new \DateTime());
        return $equipment;
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }
}