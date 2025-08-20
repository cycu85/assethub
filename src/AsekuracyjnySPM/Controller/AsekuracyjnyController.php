<?php

namespace App\AsekuracyjnySPM\Controller;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Service\AsekuracyjnyService;
use App\AsekuracyjnySPM\Form\AsekuracyjnyEquipmentType;
use App\Entity\User;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Exception\ValidationException;
use App\Exception\BusinessLogicException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/asekuracja')]
class AsekuracyjnyController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AsekuracyjnyService $asekuracyjnyService,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'asekuracja_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        // Pobranie danych
        $page = $request->query->getInt('page', 1);
        $filters = [
            'search' => $request->query->get('search'),
            'status' => $request->query->get('status'),
            'equipment_type' => $request->query->get('equipment_type'),
            'assigned_to' => $request->query->get('assigned_to'),
            'needs_review' => $request->query->getBoolean('needs_review'),
            'overdue_review' => $request->query->getBoolean('overdue_review'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $equipmentPagination = $this->asekuracyjnyService->getEquipmentWithPagination($page, 25, $filters);
        $statistics = $this->asekuracyjnyService->getEquipmentStatistics();
        
        // Sprawdzenie uprawnień do różnych akcji
        $canCreate = $this->authorizationService->hasPermission($user, 'asekuracja', 'CREATE');
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'asekuracja', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_equipment_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_equipment' => $equipmentPagination['total']
        ], $request);
        
        return $this->render('asekuracja/equipment/index.html.twig', [
            'equipment' => $equipmentPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
        ]);
    }

    #[Route('/equipment/new', name: 'asekuracja_equipment_new')]
    public function newEquipment(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'CREATE', $request);
        
        $equipment = new AsekuracyjnyEquipment();
        $form = $this->createForm(AsekuracyjnyEquipmentType::class, $equipment, [
            'include_submit' => false
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'name' => $form->get('name')->getData(),
                    'inventory_number' => $form->get('inventoryNumber')->getData(),
                    'description' => $form->get('description')->getData(),
                    'equipment_type' => $form->get('equipmentType')->getData(),
                    'manufacturer' => $form->get('manufacturer')->getData(),
                    'model' => $form->get('model')->getData(),
                    'serial_number' => $form->get('serialNumber')->getData(),
                    'manufacturing_date' => $form->get('manufacturingDate')->getData(),
                    'purchase_date' => $form->get('purchaseDate')->getData(),
                    'purchase_price' => $form->get('purchasePrice')->getData(),
                    'supplier' => $form->get('supplier')->getData(),
                    'invoice_number' => $form->get('invoiceNumber')->getData(),
                    'warranty_expiry' => $form->get('warrantyExpiry')->getData(),
                    'next_review_date' => $form->get('nextReviewDate')->getData(),
                    'review_interval_months' => $form->get('reviewIntervalMonths')->getData(),
                    'location' => $form->get('location')->getData(),
                    'notes' => $form->get('notes')->getData()
                ];

                $equipment = $this->asekuracyjnyService->createEquipment($data, $user);
                
                $this->addFlash('success', 'Sprzęt asekuracyjny został utworzony pomyślnie.');
                return $this->redirectToRoute('asekuracja_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to create asekuracyjny equipment', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('asekuracja/equipment/form.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
            'mode' => 'create'
        ]);
    }

    #[Route('/equipment/{id}', name: 'asekuracja_equipment_show', requirements: ['id' => '\d+'])]
    public function showEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        // Sprawdzenie czy użytkownik może widzieć ten sprzęt
        if (!$this->canUserViewEquipment($user, $equipment)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego sprzętu.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT') 
                   || ($equipment->getAssignedTo() === $user && $this->authorizationService->hasPermission($user, 'asekuracja', 'VIEW'));
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'asekuracja', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');
        $canTransfer = $this->authorizationService->hasPermission($user, 'asekuracja', 'TRANSFER');

        // Pobierz przeglądy posortowane chronologicznie (najnowsze pierwsze)
        $reviews = $this->entityManager->getRepository(AsekuracyjnyReview::class)
            ->createQueryBuilder('r')
            ->where('r.equipment = :equipment')
            ->setParameter('equipment', $equipment)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_equipment', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName()
        ], $request);
        
        return $this->render('asekuracja/equipment/show.html.twig', [
            'equipment' => $equipment,
            'reviews' => $reviews,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
            'can_transfer' => $canTransfer,
        ]);
    }

    #[Route('/equipment/{id}/edit', name: 'asekuracja_equipment_edit', requirements: ['id' => '\d+'])]
    public function editEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        $form = $this->createForm(AsekuracyjnyEquipmentType::class, $equipment, [
            'include_submit' => false
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'name' => $form->get('name')->getData(),
                    'inventory_number' => $form->get('inventoryNumber')->getData(),
                    'description' => $form->get('description')->getData(),
                    'equipment_type' => $form->get('equipmentType')->getData(),
                    'manufacturer' => $form->get('manufacturer')->getData(),
                    'model' => $form->get('model')->getData(),
                    'serial_number' => $form->get('serialNumber')->getData(),
                    'manufacturing_date' => $form->get('manufacturingDate')->getData(),
                    'purchase_date' => $form->get('purchaseDate')->getData(),
                    'purchase_price' => $form->get('purchasePrice')->getData(),
                    'supplier' => $form->get('supplier')->getData(),
                    'invoice_number' => $form->get('invoiceNumber')->getData(),
                    'warranty_expiry' => $form->get('warrantyExpiry')->getData(),
                    'next_review_date' => $form->get('nextReviewDate')->getData(),
                    'review_interval_months' => $form->get('reviewIntervalMonths')->getData(),
                    'location' => $form->get('location')->getData(),
                    'notes' => $form->get('notes')->getData()
                ];

                $this->asekuracyjnyService->updateEquipment($equipment, $data, $user);
                
                $this->addFlash('success', 'Sprzęt asekuracyjny został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('asekuracja_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to update asekuracyjny equipment', [
                    'equipment_id' => $equipment->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('asekuracja/equipment/form.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
            'mode' => 'edit'
        ]);
    }

    #[Route('/equipment/{id}/delete', name: 'asekuracja_equipment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'DELETE', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_equipment_' . $equipment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $equipmentName = $equipment->getName();
            $this->asekuracyjnyService->deleteEquipment($equipment, $user);
            
            $this->addFlash('success', sprintf('Sprzęt "%s" został usunięty pomyślnie.', $equipmentName));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania sprzętu.');
            $this->logger->error('Failed to delete asekuracyjny equipment', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_index');
    }

    #[Route('/equipment/{id}/assign', name: 'asekuracja_equipment_assign', requirements: ['id' => '\d+'])]
    public function assignEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'ASSIGN', $request);
        
        $form = $this->createForm(EquipmentAssignType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $assignee = $form->get('assignee')->getData();
                $notes = $form->get('notes')->getData();
                
                $this->asekuracyjnyService->assignEquipment($equipment, $assignee, $user, $notes);
                
                $this->addFlash('success', sprintf('Sprzęt został przypisany do użytkownika %s.', $assignee->getFullName()));
                return $this->redirectToRoute('asekuracja_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to assign asekuracyjny equipment', [
                    'equipment_id' => $equipment->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('asekuracja/equipment/assign.html.twig', [
            'form' => $form,
            'equipment' => $equipment
        ]);
    }

    #[Route('/equipment/{id}/unassign', name: 'asekuracja_equipment_unassign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unassignEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'ASSIGN', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('unassign_equipment_' . $equipment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $previousAssignee = $equipment->getAssignedTo();
            $this->asekuracyjnyService->unassignEquipment($equipment, $user);
            
            $this->addFlash('success', sprintf('Cofnięto przypisanie sprzętu od użytkownika %s.', $previousAssignee?->getFullName()));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
            $this->logger->error('Failed to unassign asekuracyjny equipment', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/search', name: 'asekuracja_search')]
    public function search(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }
        
        try {
            $equipment = $this->asekuracyjnyService->searchEquipment($query, 10);
            $equipmentSets = $this->asekuracyjnyService->searchEquipmentSets($query, 10);
            
            $results = [];
            
            foreach ($equipment as $item) {
                $results[] = [
                    'type' => 'equipment',
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'inventory_number' => $item->getInventoryNumber(),
                    'status' => $item->getStatusDisplayName(),
                    'url' => $this->generateUrl('asekuracja_equipment_show', ['id' => $item->getId()])
                ];
            }
            
            foreach ($equipmentSets as $item) {
                $results[] = [
                    'type' => 'equipment_set',
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'equipment_count' => $item->getEquipmentCount(),
                    'status' => $item->getStatusDisplayName(),
                    'url' => $this->generateUrl('asekuracja_equipment_set_show', ['id' => $item->getId()])
                ];
            }
            
            // Audit search
            $this->auditService->logUserAction($user, 'asekuracja_search', [
                'query' => $query,
                'results_count' => count($results)
            ], $request);
            
            return new JsonResponse([
                'results' => array_slice($results, 0, 10),
                'total' => count($results),
                'query' => $query
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Asekuracja search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd wyszukiwania'], 500);
        }
    }

    #[Route('/my-equipment', name: 'asekuracja_my_equipment')]
    public function myEquipment(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja - każdy użytkownik może zobaczyć swój przypisany sprzęt
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $assignedEquipment = $this->asekuracyjnyService->getUserAssignedEquipment($user);
        
        // Audit
        $this->auditService->logUserAction($user, 'view_my_asekuracja_equipment', [
            'equipment_count' => count($assignedEquipment['equipment']),
            'equipment_sets_count' => count($assignedEquipment['equipment_sets'])
        ], $request);
        
        return $this->render('asekuracja/my-equipment.html.twig', [
            'equipment' => $assignedEquipment['equipment'],
            'equipment_sets' => $assignedEquipment['equipment_sets']
        ]);
    }

    // === PRIVATE HELPER METHODS ===

    private function canUserViewEquipment(User $user, AsekuracyjnyEquipment $equipment): bool
    {
        // Admini i edytorzy mogą widzieć wszystko
        if ($this->authorizationService->checkAnyPermission($user, 'asekuracja', ['EDIT', 'DELETE', 'ASSIGN'])) {
            return true;
        }
        
        // Użytkownik może widzieć swój przypisany sprzęt
        if ($equipment->getAssignedTo() === $user) {
            return true;
        }
        
        // Użytkownicy z uprawnieniem VIEW mogą widzieć wszystkie
        if ($this->authorizationService->hasPermission($user, 'asekuracja', 'VIEW')) {
            return true;
        }
        
        return false;
    }
}