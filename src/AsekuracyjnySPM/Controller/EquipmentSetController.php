<?php

namespace App\AsekuracyjnySPM\Controller;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Service\AsekuracyjnyService;
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

#[Route('/asekuracja/equipment-sets')]
class EquipmentSetController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AsekuracyjnyService $asekuracyjnyService,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'asekuracja_equipment_set_index')]
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
            'set_type' => $request->query->get('set_type'),
            'assigned_to' => $request->query->get('assigned_to'),
            'needs_review' => $request->query->getBoolean('needs_review'),
            'overdue_review' => $request->query->getBoolean('overdue_review')
        ];

        $equipmentSetsPagination = $this->asekuracyjnyService->getEquipmentSetsWithPagination($page, 25, $filters);
        $statistics = $this->asekuracyjnyService->getEquipmentSetStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'asekuracja', 'CREATE');
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'asekuracja', 'ASSIGN');

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_equipment_sets_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_sets' => $equipmentSetsPagination['total']
        ], $request);
        
        return $this->render('asekuracja/equipment-set/index.html.twig', [
            'equipment_sets' => $equipmentSetsPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
        ]);
    }

    #[Route('/new', name: 'asekuracja_equipment_set_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'CREATE', $request);
        
        $equipmentSet = new AsekuracyjnyEquipmentSet();
        $form = $this->createForm(AsekuracyjnyEquipmentSetType::class, $equipmentSet);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'name' => $form->get('name')->getData(),
                    'description' => $form->get('description')->getData(),
                    'set_type' => $form->get('setType')->getData(),
                    'next_review_date' => $form->get('nextReviewDate')->getData(),
                    'review_interval_months' => $form->get('reviewIntervalMonths')->getData(),
                    'location' => $form->get('location')->getData(),
                    'notes' => $form->get('notes')->getData()
                ];

                $equipmentSet = $this->asekuracyjnyService->createEquipmentSet($data, $user);
                
                $this->addFlash('success', 'Zestaw asekuracyjny został utworzony pomyślnie.');
                return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to create asekuracyjny equipment set', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('asekuracja/equipment-set/form.html.twig', [
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'mode' => 'create'
        ]);
    }

    #[Route('/{id}', name: 'asekuracja_equipment_set_show', requirements: ['id' => '\d+'])]
    public function show(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        // Sprawdzenie czy użytkownik może widzieć ten zestaw
        if (!$this->canUserViewEquipmentSet($user, $equipmentSet)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego zestawu.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT') 
                   || ($equipmentSet->getAssignedTo() === $user && $this->authorizationService->hasPermission($user, 'asekuracja', 'VIEW'));
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'asekuracja', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');
        $canTransfer = $this->authorizationService->hasPermission($user, 'asekuracja', 'TRANSFER');
        $canManageEquipment = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT');

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_equipment_set', [
            'equipment_set_id' => $equipmentSet->getId(),
            'equipment_set_name' => $equipmentSet->getName(),
            'equipment_count' => $equipmentSet->getEquipmentCount()
        ], $request);
        
        return $this->render('asekuracja/equipment-set/show.html.twig', [
            'equipment_set' => $equipmentSet,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
            'can_transfer' => $canTransfer,
            'can_manage_equipment' => $canManageEquipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'asekuracja_equipment_set_edit', requirements: ['id' => '\d+'])]
    public function edit(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        $form = $this->createForm(AsekuracyjnyEquipmentSetType::class, $equipmentSet);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'name' => $form->get('name')->getData(),
                    'description' => $form->get('description')->getData(),
                    'set_type' => $form->get('setType')->getData(),
                    'next_review_date' => $form->get('nextReviewDate')->getData(),
                    'review_interval_months' => $form->get('reviewIntervalMonths')->getData(),
                    'location' => $form->get('location')->getData(),
                    'notes' => $form->get('notes')->getData()
                ];

                $this->asekuracyjnyService->updateEquipmentSet($equipmentSet, $data, $user);
                
                $this->addFlash('success', 'Zestaw asekuracyjny został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to update asekuracyjny equipment set', [
                    'equipment_set_id' => $equipmentSet->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('asekuracja/equipment-set/form.html.twig', [
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'mode' => 'edit'
        ]);
    }

    #[Route('/{id}/delete', name: 'asekuracja_equipment_set_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'DELETE', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_equipment_set_' . $equipmentSet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $setName = $equipmentSet->getName();
            $this->asekuracyjnyService->deleteEquipmentSet($equipmentSet, $user);
            
            $this->addFlash('success', sprintf('Zestaw "%s" został usunięty pomyślnie.', $setName));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania zestawu.');
            $this->logger->error('Failed to delete asekuracyjny equipment set', [
                'equipment_set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_index');
    }

    #[Route('/{id}/equipment/add', name: 'asekuracja_equipment_set_add_equipment', requirements: ['id' => '\d+'])]
    public function addEquipment(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        if ($request->isMethod('POST')) {
            $equipmentIds = $request->request->all('equipment_ids');
            
            if (!empty($equipmentIds)) {
                try {
                    $addedCount = 0;
                    foreach ($equipmentIds as $equipmentId) {
                        $equipment = $this->asekuracyjnyService->getEquipmentRepository()->find($equipmentId);
                        if ($equipment && !$equipmentSet->getEquipment()->contains($equipment)) {
                            $this->asekuracyjnyService->addEquipmentToSet($equipmentSet, $equipment, $user);
                            $addedCount++;
                        }
                    }
                    
                    $this->addFlash('success', sprintf('Dodano %d elementów do zestawu.', $addedCount));
                    return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
                    
                } catch (BusinessLogicException $e) {
                    $this->addFlash('error', $e->getMessage());
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                    $this->logger->error('Failed to add equipment to set', [
                        'equipment_set_id' => $equipmentSet->getId(),
                        'error' => $e->getMessage(),
                        'user' => $user->getUsername()
                    ]);
                }
            }
        }
        
        // Pobranie dostępnego sprzętu (nie przypisanego do żadnego zestawu lub dostępnego)
        $availableEquipment = $this->asekuracyjnyService->getAvailableEquipment();
        
        return $this->render('asekuracja/equipment-set/add-equipment.html.twig', [
            'equipment_set' => $equipmentSet,
            'available_equipment' => $availableEquipment
        ]);
    }

    #[Route('/{id}/equipment/{equipmentId}/remove', name: 'asekuracja_equipment_set_remove_equipment', requirements: ['id' => '\d+', 'equipmentId' => '\d+'], methods: ['POST'])]
    public function removeEquipment(AsekuracyjnyEquipmentSet $equipmentSet, int $equipmentId, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('remove_equipment_' . $equipmentSet->getId() . '_' . $equipmentId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $equipment = $this->asekuracyjnyService->getEquipmentRepository()->find($equipmentId);
            if (!$equipment) {
                throw new \InvalidArgumentException('Sprzęt nie został znaleziony.');
            }
            
            $equipmentName = $equipment->getName();
            $this->asekuracyjnyService->removeEquipmentFromSet($equipmentSet, $equipment, $user);
            
            $this->addFlash('success', sprintf('Usunięto "%s" z zestawu.', $equipmentName));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
            $this->logger->error('Failed to remove equipment from set', [
                'equipment_set_id' => $equipmentSet->getId(),
                'equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/available-equipment', name: 'asekuracja_available_equipment_modal')]
    public function availableEquipmentModal(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        $limit = 20;
        
        try {
            $filters = [
                'search' => $search,
                'status' => AsekuracyjnyEquipment::STATUS_AVAILABLE
            ];
            
            $pagination = $this->asekuracyjnyService->getEquipmentWithPagination($page, $limit, $filters);
            
            $equipment = array_map(function ($item) {
                return [
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'inventory_number' => $item->getInventoryNumber(),
                    'equipment_type' => $item->getEquipmentType(),
                    'manufacturer' => $item->getManufacturer(),
                    'model' => $item->getModel(),
                    'status' => $item->getStatusDisplayName()
                ];
            }, $pagination['items']);
            
            return new JsonResponse([
                'equipment' => $equipment,
                'pagination' => [
                    'page' => $pagination['page'],
                    'pages' => $pagination['pages'],
                    'total' => $pagination['total']
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to load available equipment', [
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd wczytywania sprzętu'], 500);
        }
    }

    // === PRIVATE HELPER METHODS ===

    private function canUserViewEquipmentSet(User $user, AsekuracyjnyEquipmentSet $equipmentSet): bool
    {
        // Admini i edytorzy mogą widzieć wszystko
        if ($this->authorizationService->hasAnyPermission($user, 'asekuracja', ['EDIT', 'DELETE', 'ASSIGN'])) {
            return true;
        }
        
        // Użytkownik może widzieć swój przypisany zestaw
        if ($equipmentSet->getAssignedTo() === $user) {
            return true;
        }
        
        // Użytkownicy z uprawnieniem VIEW mogą widzieć wszystkie
        if ($this->authorizationService->hasPermission($user, 'asekuracja', 'VIEW')) {
            return true;
        }
        
        return false;
    }
}