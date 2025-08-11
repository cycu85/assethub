<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\PermissionService;
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
        private PermissionService $permissionService,
        private RateLimiterFactory $searchLimiter
    ) {}

    #[Route('/api/search', name: 'api_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        // Rate limiting - sprawdź czy użytkownik nie przekroczył limitu wyszukiwań
        $limiter = $this->searchLimiter->create($this->getUser()->getId());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(60, 'Too many search requests. Please wait a moment.');
        }
        
        $query = trim($request->query->get('q', ''));
        
        if (strlen($query) < 2) {
            return new JsonResponse([
                'users' => [],
                'message' => 'Wprowadź co najmniej 2 znaki'
            ]);
        }

        $results = [];
        $user = $this->getUser();

        // Wyszukiwanie użytkowników/pracowników (jeśli ma uprawnienia)
        if ($this->permissionService->canAccessModule($user, 'employees')) {
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
        }

        // TODO: W przyszłości dodać wyszukiwanie aktywów, gdy będzie gotowy moduł assets
        // if ($this->permissionService->canAccessModule($user, 'assets')) {
        //     $assets = $this->searchAssets($query);
        //     // ...
        // }

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
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();
    }
}