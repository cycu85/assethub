<?php

namespace App\Controller;

use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Service\EquipmentService;
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
        private EquipmentService $equipmentService,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'dashboard')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Get user available modules via AuthorizationService
        $availableModules = $this->authorizationService->getUserModules($user);
        
        // Get dashboard statistics via EquipmentService
        $dashboardStats = $this->getDashboardStats($user);

        // Audit dashboard access
        $this->auditService->logUserAction($user, 'view_dashboard', [
            'modules_count' => count($availableModules),
            'has_equipment_access' => in_array('equipment', array_column($availableModules, 'name'))
        ], $request);

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'modules' => $availableModules,
            'stats' => $dashboardStats,
        ]);
    }

    private function getDashboardStats($user): array
    {
        $stats = [
            'total_equipment' => 0,
            'my_equipment' => 0,
            'available_equipment' => 0,
            'damaged_equipment' => 0,
            'recent_activities' => []
        ];
        
        // Only get equipment stats if user has equipment access
        if ($this->authorizationService->hasAnyPermission($user, 'equipment', ['VIEW', 'EDIT'])) {
            try {
                $equipmentStats = $this->equipmentService->getEquipmentStatistics();
                $stats = array_merge($stats, $equipmentStats);
                
                // Get user's assigned equipment count
                $userEquipment = $this->equipmentService->getUserAssignedEquipment($user);
                $stats['my_equipment'] = count($userEquipment);
            } catch (\Exception $e) {
                // Log error but don't break dashboard
                $this->logger->warning('Failed to load dashboard equipment stats', [
                    'user' => $user->getUsername(),
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $stats;
    }
}