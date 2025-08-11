<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\EquipmentLog;
use App\Form\EquipmentType;
use App\Repository\EquipmentRepository;
use App\Repository\EquipmentCategoryRepository;
use App\Service\AuthorizationService;
use App\Service\EquipmentService;
use App\Service\AuditService;
use App\Query\Equipment\GetEquipmentQuery;
use App\Handler\Equipment\GetEquipmentQueryHandler;
use App\Command\Equipment\CreateEquipmentCommand;
use App\Handler\Equipment\CreateEquipmentCommandHandler;
use App\Event\Equipment\EquipmentCreatedEvent;
use App\Event\Equipment\EquipmentAssignedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private EquipmentService $equipmentService,
        private AuditService $auditService,
        private EventDispatcherInterface $eventDispatcher,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'equipment_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'equipment', $request);

        // Create query using CQRS pattern
        $query = new GetEquipmentQuery(
            page: $request->query->getInt('page', 1),
            limit: 25,
            search: $request->query->get('search'),
            status: $request->query->get('status'),
            categoryId: $request->query->get('category') ? (int) $request->query->get('category') : null,
            warrantyExpiring: $request->query->getBoolean('warranty_expiring')
        );

        // Execute query via handler
        $equipment = $this->equipmentService->getEquipmentWithPagination(
            $query->page,
            $query->limit,
            $query->getFilters()
        );

        // Get supporting data
        $categories = $this->equipmentService->getActiveCategories();
        $statistics = $this->equipmentService->getEquipmentStatistics();

        // Audit via AuditService
        $this->auditService->logUserAction($user, 'view_equipment_index', [
            'page' => $query->page,
            'filters' => array_filter($query->getFilters()),
            'total_equipment' => $equipment->getTotalItemCount()
        ], $request);

        return $this->render('equipment/index.html.twig', [
            'equipment' => $equipment,
            'categories' => $categories,
            'statistics' => $statistics,
            'can_create' => $this->authorizationService->hasPermission($user, 'equipment', 'CREATE'),
            'can_edit' => $this->authorizationService->hasPermission($user, 'equipment', 'EDIT'),
            'can_delete' => $this->authorizationService->hasPermission($user, 'equipment', 'DELETE'),
        ]);
    }

    #[Route('/new', name: 'equipment_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkPermission($user, 'equipment', 'CREATE', $request);

        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Use CQRS Command pattern
            $command = new CreateEquipmentCommand(
                name: $equipment->getName(),
                description: $equipment->getDescription(),
                inventoryNumber: $equipment->getInventoryNumber(),
                serialNumber: $equipment->getSerialNumber(),
                manufacturer: $equipment->getManufacturer(),
                model: $equipment->getModel(),
                categoryId: $equipment->getCategory()?->getId(),
                status: $equipment->getStatus() ?? 'available',
                purchasePrice: $equipment->getPurchasePrice(),
                purchaseDate: $equipment->getPurchaseDate(),
                warrantyExpiry: $equipment->getWarrantyExpiry(),
                location: $equipment->getLocation(),
                createdById: $user->getId()
            );

            // Execute via service (which will handle persistence and logging)
            $createdEquipment = $this->equipmentService->createEquipment($command->toArray(), $user);

            // Dispatch domain event
            $event = new EquipmentCreatedEvent($createdEquipment, $user, [
                'source' => 'web_form',
                'ip' => $request->getClientIp()
            ]);
            $this->eventDispatcher->dispatch($event, EquipmentCreatedEvent::NAME);

            $this->addFlash('success', 'Sprzęt został dodany pomyślnie.');

            return $this->redirectToRoute('equipment_index');
        }

        $this->logger->info('Equipment new form accessed', [
            'user' => $user->getUsername(),
            'ip' => $request->getClientIp()
        ]);

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'equipment_show', requirements: ['id' => '\d+'])]
    public function show(Equipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkPermission($user, 'equipment', 'VIEW', $request);

        // Audit via AuditService
        $this->auditService->logUserAction($user, 'view_equipment', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName(),
            'inventory_number' => $equipment->getInventoryNumber()
        ], $request);

        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
            'can_edit' => $this->authorizationService->hasPermission($user, 'equipment', 'EDIT'),
            'can_delete' => $this->authorizationService->hasPermission($user, 'equipment', 'DELETE'),
        ]);
    }

    #[Route('/{id}/edit', name: 'equipment_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Equipment $equipment): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkPermission($user, 'equipment', 'EDIT', $request);

        $originalStatus = $equipment->getStatus();
        $originalAssignee = $equipment->getAssignedTo();
        
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Update via EquipmentService
            $updatedEquipment = $this->equipmentService->updateEquipment($equipment, [
                'original_status' => $originalStatus,
                'original_assignee' => $originalAssignee,
            ], $user);

            // Dispatch assignment event if assignee changed
            if ($originalAssignee !== $equipment->getAssignedTo() && $equipment->getAssignedTo()) {
                $event = new EquipmentAssignedEvent(
                    $equipment,
                    $equipment->getAssignedTo(),
                    $originalAssignee,
                    $user,
                    'Przypisane podczas edycji',
                    ['source' => 'edit_form', 'ip' => $request->getClientIp()]
                );
                $this->eventDispatcher->dispatch($event, EquipmentAssignedEvent::NAME);
            }

            $this->addFlash('success', 'Sprzęt został zaktualizowany pomyślnie.');

            return $this->redirectToRoute('equipment_show', ['id' => $equipment->getId()]);
        }

        // Audit form access
        $this->auditService->logUserAction($user, 'access_equipment_edit_form', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName()
        ], $request);

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'equipment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkPermission($user, 'equipment', 'DELETE', $request);

        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->request->get('_token'))) {
            // Delete via EquipmentService
            $this->equipmentService->deleteEquipment($equipment, $user);

            $this->addFlash('success', 'Sprzęt został usunięty pomyślnie.');
        } else {
            // Audit invalid CSRF attempt
            $this->auditService->logSecurityEvent('invalid_csrf_token_delete_equipment', $user, [
                'equipment_id' => $equipment->getId(),
                'equipment_name' => $equipment->getName(),
                'token_received' => $request->request->get('_token')
            ], $request);
        }

        return $this->redirectToRoute('equipment_index');
    }

    #[Route('/category/{id}', name: 'equipment_by_category', requirements: ['id' => '\d+'])]
    public function byCategory(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'equipment', $request);

        // Get equipment by category via service
        $result = $this->equipmentService->getEquipmentByCategory($id);
        
        if (!$result['category']) {
            throw $this->createNotFoundException('Kategoria nie została znaleziona');
        }

        // Audit category access
        $this->auditService->logUserAction($user, 'view_equipment_by_category', [
            'category_id' => $id,
            'category_name' => $result['category']->getName(),
            'equipment_count' => count($result['equipment'])
        ], $request);

        return $this->render('equipment/by_category.html.twig', [
            'equipment' => $result['equipment'],
            'category' => $result['category'],
            'can_create' => $this->authorizationService->hasPermission($user, 'equipment', 'CREATE'),
            'can_edit' => $this->authorizationService->hasPermission($user, 'equipment', 'EDIT'),
            'can_delete' => $this->authorizationService->hasPermission($user, 'equipment', 'DELETE'),
        ]);
    }

    #[Route('/my', name: 'equipment_my')]
    public function myEquipment(Request $request): Response
    {
        $user = $this->getUser();
        
        // Authorization via AuthorizationService
        $this->authorizationService->checkModuleAccess($user, 'equipment', $request);

        // Get user's assigned equipment via service
        $equipment = $this->equipmentService->getUserAssignedEquipment($user);

        // Audit access to personal equipment
        $this->auditService->logUserAction($user, 'view_my_equipment', [
            'assigned_equipment_count' => count($equipment)
        ], $request);

        return $this->render('equipment/my.html.twig', [
            'equipment' => $equipment,
        ]);
    }
}