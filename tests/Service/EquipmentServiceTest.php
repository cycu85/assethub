<?php

namespace App\Tests\Service;

use App\Entity\Equipment;
use App\Entity\EquipmentCategory;
use App\Entity\EquipmentLog;
use App\Entity\User;
use App\Exception\BusinessLogicException;
use App\Exception\ValidationException;
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentCategoryRepository;
use App\Repository\EquipmentLogRepository;
use App\Service\AuditService;
use App\Service\EquipmentService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EquipmentServiceTest extends TestCase
{
    private EquipmentService $equipmentService;
    private MockObject|EquipmentRepository $equipmentRepositoryMock;
    private MockObject|EquipmentCategoryRepository $categoryRepositoryMock;
    private MockObject|EquipmentLogRepository $logRepositoryMock;
    private MockObject|EntityManagerInterface $entityManagerMock;
    private MockObject|ValidatorInterface $validatorMock;
    private MockObject|AuditService $auditServiceMock;
    private MockObject|LoggerInterface $loggerMock;
    private MockObject|PaginatorInterface $paginatorMock;

    protected function setUp(): void
    {
        $this->equipmentRepositoryMock = $this->createMock(EquipmentRepository::class);
        $this->categoryRepositoryMock = $this->createMock(EquipmentCategoryRepository::class);
        $this->logRepositoryMock = $this->createMock(EquipmentLogRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->validatorMock = $this->createMock(ValidatorInterface::class);
        $this->auditServiceMock = $this->createMock(AuditService::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->paginatorMock = $this->createMock(PaginatorInterface::class);

        $this->equipmentService = new EquipmentService(
            $this->equipmentRepositoryMock,
            $this->categoryRepositoryMock,
            $this->logRepositoryMock,
            $this->entityManagerMock,
            $this->validatorMock,
            $this->auditServiceMock,
            $this->loggerMock,
            $this->paginatorMock
        );
    }

    public function testCreateEquipmentSuccessfully(): void
    {
        // Arrange
        $equipmentData = [
            'name' => 'Test Equipment',
            'description' => 'Test Description',
            'inventoryNumber' => 'INV001',
            'status' => 'available'
        ];
        $user = $this->createTestUser();

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Equipment::class));

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->auditServiceMock->expects($this->once())
            ->method('logCrudOperation')
            ->with(
                $user,
                'Equipment',
                $this->isType('int'),
                'CREATE',
                $equipmentData
            );

        $this->loggerMock->expects($this->once())
            ->method('info')
            ->with('Equipment created successfully', $this->arrayHasKey('equipment_id'));

        // Act
        $equipment = $this->equipmentService->createEquipment($equipmentData, $user);

        // Assert
        $this->assertInstanceOf(Equipment::class, $equipment);
        $this->assertEquals('Test Equipment', $equipment->getName());
        $this->assertEquals('available', $equipment->getStatus());
        $this->assertEquals($user, $equipment->getCreatedBy());
    }

    public function testCreateEquipmentThrowsValidationException(): void
    {
        // Arrange
        $equipmentData = ['name' => '']; // Invalid data
        $user = $this->createTestUser();

        $violation = new ConstraintViolation(
            'Name cannot be empty',
            null,
            [],
            null,
            'name',
            ''
        );
        $violations = new ConstraintViolationList([$violation]);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn($violations);

        $this->entityManagerMock->expects($this->never())
            ->method('persist');

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation errors occurred');

        // Act
        $this->equipmentService->createEquipment($equipmentData, $user);
    }

    public function testUpdateEquipmentSuccessfully(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $originalStatus = $equipment->getStatus();
        $updateData = ['status' => 'maintenance'];
        $context = ['original_status' => $originalStatus];
        $user = $this->createTestUser();

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($equipment);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Act
        $updatedEquipment = $this->equipmentService->updateEquipment($equipment, $context, $user);

        // Assert
        $this->assertInstanceOf(Equipment::class, $updatedEquipment);
        $this->assertEquals($user, $updatedEquipment->getUpdatedBy());
    }

    public function testDeleteEquipmentSuccessfully(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $user = $this->createTestUser();

        $this->entityManagerMock->expects($this->once())
            ->method('remove')
            ->with($equipment);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->auditServiceMock->expects($this->once())
            ->method('logCrudOperation')
            ->with(
                $user,
                'Equipment',
                $equipment->getId(),
                'DELETE',
                $this->arrayHasKey('equipment_name')
            );

        $this->loggerMock->expects($this->once())
            ->method('warning')
            ->with('Equipment deleted', $this->arrayHasKey('equipment_id'));

        // Act
        $this->equipmentService->deleteEquipment($equipment, $user);
    }

    public function testAssignEquipmentSuccessfully(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $assignee = $this->createTestUser();
        $assignedBy = $this->createTestUser();
        $notes = 'Assigned for project work';

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($equipment);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->equipmentService->assignEquipment($equipment, $assignee, $assignedBy, $notes);

        // Assert
        $this->assertInstanceOf(Equipment::class, $result);
        $this->assertEquals($assignee, $result->getAssignedTo());
        $this->assertEquals('assigned', $result->getStatus());
    }

    public function testAssignEquipmentThrowsExceptionWhenNotAvailable(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $equipment->setStatus('maintenance');
        $assignee = $this->createTestUser();
        $assignedBy = $this->createTestUser();

        // Assert
        $this->expectException(BusinessLogicException::class);
        $this->expectExceptionMessage('Equipment is not available for assignment');

        // Act
        $this->equipmentService->assignEquipment($equipment, $assignee, $assignedBy);
    }

    public function testUnassignEquipmentSuccessfully(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $assignee = $this->createTestUser();
        $equipment->setAssignedTo($assignee);
        $equipment->setStatus('assigned');
        $user = $this->createTestUser();

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($equipment);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->equipmentService->unassignEquipment($equipment, $user);

        // Assert
        $this->assertInstanceOf(Equipment::class, $result);
        $this->assertNull($result->getAssignedTo());
        $this->assertEquals('available', $result->getStatus());
    }

    public function testMarkAsDamagedSuccessfully(): void
    {
        // Arrange
        $equipment = $this->createTestEquipment();
        $user = $this->createTestUser();
        $description = 'Screen is cracked';

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($equipment);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->equipmentService->markAsDamaged($equipment, $user, $description);

        // Assert
        $this->assertInstanceOf(Equipment::class, $result);
        $this->assertEquals('damaged', $result->getStatus());
    }

    public function testGetEquipmentStatisticsReturnsCorrectData(): void
    {
        // Arrange
        $statusStats = [
            ['status' => 'available', 'count' => 10],
            ['status' => 'assigned', 'count' => 5],
            ['status' => 'maintenance', 'count' => 2]
        ];
        $totalValue = 50000.00;

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('getStatisticsByStatus')
            ->willReturn($statusStats);

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('getTotalValue')
            ->willReturn($totalValue);

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('findDueForInspection')
            ->willReturn([]);

        // Act
        $statistics = $this->equipmentService->getEquipmentStatistics();

        // Assert
        $this->assertEquals(17, $statistics['total']); // 10 + 5 + 2
        $this->assertEquals($statusStats, $statistics['by_status']);
        $this->assertEquals($totalValue, $statistics['total_value']);
        $this->assertEquals(0, $statistics['due_for_inspection']);
        $this->assertArrayHasKey('warranty_expiring', $statistics);
    }

    public function testSearchEquipmentReturnsResults(): void
    {
        // Arrange
        $query = 'laptop';
        $limit = 5;
        $expectedResults = [$this->createTestEquipment()];

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('findBySearchTerm')
            ->with($query)
            ->willReturn($expectedResults);

        // Act
        $results = $this->equipmentService->searchEquipment($query, $limit);

        // Assert
        $this->assertEquals($expectedResults, $results);
    }

    public function testGetUserAssignedEquipmentReturnsUserEquipment(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $expectedEquipment = [$this->createTestEquipment()];

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('findBy')
            ->with(['assignedTo' => $user])
            ->willReturn($expectedEquipment);

        // Act
        $equipment = $this->equipmentService->getUserAssignedEquipment($user);

        // Assert
        $this->assertEquals($expectedEquipment, $equipment);
    }

    public function testGetEquipmentByCategoryReturnsCorrectData(): void
    {
        // Arrange
        $categoryId = 1;
        $category = new EquipmentCategory();
        $category->setName('Laptops');
        $equipment = [$this->createTestEquipment()];

        $this->categoryRepositoryMock->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $this->equipmentRepositoryMock->expects($this->once())
            ->method('findBy')
            ->with(['category' => $category])
            ->willReturn($equipment);

        // Act
        $result = $this->equipmentService->getEquipmentByCategory($categoryId);

        // Assert
        $this->assertEquals($category, $result['category']);
        $this->assertEquals($equipment, $result['equipment']);
    }

    public function testGetEquipmentByCategoryReturnsNullForNonexistentCategory(): void
    {
        // Arrange
        $categoryId = 999;

        $this->categoryRepositoryMock->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn(null);

        $this->equipmentRepositoryMock->expects($this->never())
            ->method('findBy');

        // Act
        $result = $this->equipmentService->getEquipmentByCategory($categoryId);

        // Assert
        $this->assertNull($result['category']);
        $this->assertEquals([], $result['equipment']);
    }

    public function testGetActiveCategories(): void
    {
        // Arrange
        $expectedCategories = [new EquipmentCategory()];

        $this->categoryRepositoryMock->expects($this->once())
            ->method('findActive')
            ->willReturn($expectedCategories);

        // Act
        $categories = $this->equipmentService->getActiveCategories();

        // Assert
        $this->assertEquals($expectedCategories, $categories);
    }

    private function createTestUser(): User
    {
        $user = new User();
        $user->setId(123)
             ->setUsername('testuser')
             ->setEmail('test@example.com');
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
}