<?php

namespace App\Controller;

use App\Service\AuthorizationService;
use App\Service\AuditService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'dashboard')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get user available modules via AuthorizationService
        $availableModules = $this->authorizationService->getUserModules($user);
        
        // Audit dashboard access
        $this->auditService->logUserAction($user, 'view_dashboard', [
            'modules_count' => count($availableModules)
        ], $request);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'modules' => $availableModules,
        ]);
    }
}