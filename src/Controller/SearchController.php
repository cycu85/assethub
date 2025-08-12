<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Service\EquipmentService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class SearchController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private EquipmentService $equipmentService,
        private RateLimiterFactory $searchLimiter,
        private LoggerInterface $logger
    ) {}

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Rate limiting - sprawdź czy użytkownik nie przekroczył limitu wyszukiwań
        $limiter = $this->searchLimiter->create($user->getId());
        if (!$limiter->consume(1)->isAccepted()) {
            $this->auditService->logSecurityEvent('search_rate_limit_exceeded', $user, [
                'attempted_query' => $request->query->get('q', '')
            ], $request);
            throw new TooManyRequestsHttpException(60, 'Too many search requests. Please wait a moment.');
        }
        
        $query = trim($request->query->get('q', ''));
        
        if (strlen($query) < 2) {
            return new JsonResponse([
                'results' => [],
                'message' => 'Wprowadź co najmniej 2 znaki'
            ]);
        }

        $results = [];
        $searchStats = ['types_searched' => []];

        try {
            // Wyszukiwanie użytkowników/pracowników (jeśli ma uprawnienia)
            if ($this->authorizationService->hasModuleAccess($user, 'employees')) {
                $users = $this->searchUsers($query);
                foreach ($users as $foundUser) {
                    $results[] = [
                        'type' => 'user',
                        'title' => $foundUser->getFullName(),
                        'subtitle' => $foundUser->getEmail() . ($foundUser->getPosition() ? ' • ' . $foundUser->getPosition() : ''),
                        'url' => $this->generateUrl('admin_users_edit', ['id' => $foundUser->getId()]),
                        'icon' => 'ri-user-line',
                        'badge' => $foundUser->getDepartment()
                    ];
                }
                $searchStats['types_searched'][] = 'users';
                $searchStats['users_found'] = count($users);
            }

            // Wyszukiwanie sprzętu (jeśli ma uprawnienia)
            if ($this->authorizationService->checkAnyPermission($user, 'equipment', ['VIEW'])) {
                $equipment = $this->searchEquipment($query);
                foreach ($equipment as $item) {
                    $results[] = [
                        'type' => 'equipment',
                        'title' => $item->getName(),
                        'subtitle' => 'Nr inwentarzowy: ' . ($item->getInventoryNumber() ?? 'Brak') . 
                                     ($item->getModel() ? ' • ' . $item->getModel() : ''),
                        'url' => $this->generateUrl('equipment_show', ['id' => $item->getId()]),
                        'icon' => 'ri-computer-line',
                        'badge' => $item->getStatus()
                    ];
                }
                $searchStats['types_searched'][] = 'equipment';
                $searchStats['equipment_found'] = count($equipment);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Search operation failed', [
                'user' => $user->getUsername(),
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            
            return new JsonResponse([
                'results' => [],
                'error' => 'Wystąpił błąd podczas wyszukiwania',
                'query' => $query
            ], 500);
        }
        
        // Audit search operation
        $this->auditService->logUserAction($user, 'global_search', array_merge([
            'query' => $query,
            'results_count' => count($results)
        ], $searchStats), $request);

        return new JsonResponse([
            'results' => array_slice($results, 0, 10), // Maksymalnie 10 wyników
            'total' => count($results),
            'query' => $query
        ]);
    }

    private function searchUsers(string $query): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->andWhere('(
                u.firstName LIKE :query OR 
                u.lastName LIKE :query OR 
                u.email LIKE :query OR 
                u.employeeNumber LIKE :query OR
                u.position LIKE :query OR
                u.department LIKE :query
            )')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
    
    private function searchEquipment(string $query): array
    {
        try {
            return $this->equipmentService->searchEquipment($query, 5);
        } catch (\Exception $e) {
            $this->logger->warning('Equipment search failed', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}