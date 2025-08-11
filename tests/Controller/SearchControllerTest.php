<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\Equipment;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Service\EquipmentService;
use App\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\RateLimit;

class SearchControllerTest extends WebTestCase
{
    private $client;
    private MockObject|AuthorizationService $authorizationServiceMock;
    private MockObject|AuditService $auditServiceMock;
    private MockObject|EquipmentService $equipmentServiceMock;
    private MockObject|UserRepository $userRepositoryMock;
    private MockObject|RateLimiterFactory $rateLimiterFactoryMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        
        // Mock services
        $this->authorizationServiceMock = $this->createMock(AuthorizationService::class);
        $this->auditServiceMock = $this->createMock(AuditService::class);
        $this->equipmentServiceMock = $this->createMock(EquipmentService::class);
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->rateLimiterFactoryMock = $this->createMock(RateLimiterFactory::class);
        
        // Replace services in container
        $container = self::getContainer();
        $container->set(AuthorizationService::class, $this->authorizationServiceMock);
        $container->set(AuditService::class, $this->auditServiceMock);
        $container->set(EquipmentService::class, $this->equipmentServiceMock);
        $container->set(UserRepository::class, $this->userRepositoryMock);
        $container->set(RateLimiterFactory::class, $this->rateLimiterFactoryMock);
    }

    public function testSearchActionRequiresAuthentication(): void
    {
        // Act
        $this->client->request('GET', '/api/search?q=test');

        // Assert
        $this->assertResponseRedirects('/login');
    }

    public function testSearchActionWithValidQueryReturnsResults(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Mock rate limiter
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), true, 10);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->with($user->getId())
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->with(1)
            ->willReturn($rateLimit);

        // Mock authorization
        $this->authorizationServiceMock->expects($this->once())
            ->method('hasModuleAccess')
            ->with($user, 'employees')
            ->willReturn(true);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW'])
            ->willReturn(true);

        // Mock user search results
        $mockUsers = [
            $this->createSearchUser('John Doe', 'john@example.com', 'Developer'),
            $this->createSearchUser('Jane Smith', 'jane@example.com', 'Manager')
        ];

        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($mockUsers);

        // Mock equipment search results
        $mockEquipment = [
            $this->createSearchEquipment('Laptop Dell', 'INV001'),
            $this->createSearchEquipment('Monitor Samsung', 'INV002')
        ];

        $this->equipmentServiceMock->expects($this->once())
            ->method('searchEquipment')
            ->with('laptop', 5)
            ->willReturn($mockEquipment);

        // Mock audit logging
        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction')
            ->with(
                $user,
                'global_search',
                $this->callback(function($data) {
                    return $data['query'] === 'laptop' &&
                           $data['results_count'] === 4 &&
                           in_array('users', $data['types_searched']) &&
                           in_array('equipment', $data['types_searched']) &&
                           $data['users_found'] === 2 &&
                           $data['equipment_found'] === 2;
                })
            );

        // Act
        $this->client->request('GET', '/api/search?q=laptop');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('laptop', $response['query']);
        $this->assertEquals(4, $response['total']);
        $this->assertCount(4, $response['results']);
        
        // Check user results
        $userResults = array_filter($response['results'], fn($r) => $r['type'] === 'user');
        $this->assertCount(2, $userResults);

        // Check equipment results
        $equipmentResults = array_filter($response['results'], fn($r) => $r['type'] === 'equipment');
        $this->assertCount(2, $equipmentResults);
    }

    public function testSearchActionWithShortQueryReturnsError(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Mock rate limiter
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), true, 10);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit);

        // Act
        $this->client->request('GET', '/api/search?q=x');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEmpty($response['results']);
        $this->assertEquals('Wprowadź co najmniej 2 znaki', $response['message']);
    }

    public function testSearchActionRateLimitExceeded(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Mock rate limiter that rejects request
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), false, 0);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit);

        // Mock security event logging
        $this->auditServiceMock->expects($this->once())
            ->method('logSecurityEvent')
            ->with('search_rate_limit_exceeded', $user, ['attempted_query' => 'test']);

        // Act & Assert
        $this->client->request('GET', '/api/search?q=test');
        $this->assertResponseStatusCodeSame(429);
    }

    public function testSearchActionWithoutEmployeeAccessSkipsUserSearch(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Mock rate limiter
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), true, 10);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit);

        // User has no employee module access
        $this->authorizationServiceMock->expects($this->once())
            ->method('hasModuleAccess')
            ->with($user, 'employees')
            ->willReturn(false);

        // But has equipment access
        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->with($user, 'equipment', ['VIEW'])
            ->willReturn(true);

        // Should not search users
        $this->userRepositoryMock->expects($this->never())
            ->method('createQueryBuilder');

        // Should search equipment
        $this->equipmentServiceMock->expects($this->once())
            ->method('searchEquipment')
            ->willReturn([]);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction');

        // Act
        $this->client->request('GET', '/api/search?q=laptop');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(0, $response['total']);
    }

    public function testSearchActionHandlesEquipmentServiceException(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Mock rate limiter
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), true, 10);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasModuleAccess')
            ->willReturn(false);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->willReturn(true);

        // Equipment service throws exception
        $this->equipmentServiceMock->expects($this->once())
            ->method('searchEquipment')
            ->willThrowException(new \Exception('Database error'));

        // Act
        $this->client->request('GET', '/api/search?q=laptop');

        // Assert
        $this->assertResponseStatusCodeSame(500);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertEquals('Wystąpił błąd podczas wyszukiwania', $response['error']);
    }

    public function testSearchActionLimitsResultsToTen(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->loginUser($user);

        // Create more than 10 mock results
        $manyUsers = [];
        for ($i = 1; $i <= 15; $i++) {
            $manyUsers[] = $this->createSearchUser("User $i", "user$i@example.com", 'Employee');
        }

        // Mock rate limiter and authorization
        $rateLimiterMock = $this->createMock(\Symfony\Component\RateLimiter\RateLimiterInterface::class);
        $rateLimit = new RateLimit(10, new \DateTimeImmutable(), true, 10);
        
        $this->rateLimiterFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($rateLimiterMock);

        $rateLimiterMock->expects($this->once())
            ->method('consume')
            ->willReturn($rateLimit);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasModuleAccess')
            ->willReturn(true);

        $this->authorizationServiceMock->expects($this->once())
            ->method('hasAnyPermission')
            ->willReturn(false);

        // Mock user search with many results
        $queryBuilder = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\Query::class);

        $this->userRepositoryMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('orderBy')->willReturnSelf();
        $queryBuilder->method('addOrderBy')->willReturnSelf();
        $queryBuilder->method('setMaxResults')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($manyUsers);

        $this->auditServiceMock->expects($this->once())
            ->method('logUserAction');

        // Act
        $this->client->request('GET', '/api/search?q=user');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Should be limited to 10 results even though 15 were found
        $this->assertCount(10, $response['results']);
        $this->assertEquals(15, $response['total']); // Total should still show all found
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

    private function createSearchUser(string $name, string $email, string $position): User
    {
        $user = new User();
        $parts = explode(' ', $name);
        $user->setFirstName($parts[0])
             ->setLastName($parts[1] ?? '')
             ->setEmail($email)
             ->setPosition($position)
             ->setDepartment('IT');
        return $user;
    }

    private function createSearchEquipment(string $name, string $inventoryNumber): Equipment
    {
        $equipment = new Equipment();
        $equipment->setName($name)
                  ->setInventoryNumber($inventoryNumber)
                  ->setStatus('available');
        return $equipment;
    }

    private function loginUser(User $user): void
    {
        $this->client->loginUser($user);
    }
}