<?php

namespace App\AparaturaPomiarowa\Controller;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaTransferService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaPdfService;
use App\AparaturaPomiarowa\Form\AparaturaPomiarowaEquipmentSetType;
use App\AparaturaPomiarowa\Form\AparaturaPomiarowaTransferType;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/aparatura-pomiarowa/equipment-sets')]
class AparaturaPomiarowaEquipmentSetController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AparaturaPomiarowaService $aparaturaService,
        private AparaturaPomiarowaTransferService $transferService,
        private AparaturaPomiarowaPdfService $pdfService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'aparatura_pomiarowa_equipment_set_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Pobranie danych
        $page = $request->query->getInt('page', 1);
        $filters = [
            'search' => $request->query->get('search'),
            'status' => $request->query->get('status'),
            'set_type' => $request->query->get('set_type'),
            'assigned_to' => $request->query->get('assigned_to'),
            'needs_calibration' => $request->query->getBoolean('needs_calibration'),
            'overdue_calibration' => $request->query->getBoolean('overdue_calibration'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $equipmentSetsPagination = $this->aparaturaService->getEquipmentSetsWithPagination($page, 1000, $filters);
        $statistics = $this->aparaturaService->getEquipmentSetStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'CREATE');
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_equipment_sets_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_sets' => $equipmentSetsPagination['total']
        ], $request);
        
        return $this->render('aparatura-pomiarowa/equipment-set/index.html.twig', [
            'equipment_sets' => $equipmentSetsPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
        ]);
    }

    #[Route('/new', name: 'aparatura_pomiarowa_equipment_set_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'CREATE', $request);
        
        $equipmentSet = new AparaturaPomiarowaEquipmentSet();
        $form = $this->createForm(AparaturaPomiarowaEquipmentSetType::class, $equipmentSet, [
            'include_submit' => false
        ]);
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

                $equipmentSet = $this->aparaturaService->createEquipmentSet($data, $user);
                
                $this->addFlash('success', 'Zestaw aparatury pomiarowej został utworzony pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to create aparatura pomiarowa equipment set', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/equipment-set/form.html.twig', [
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'mode' => 'create'
        ]);
    }

    #[Route('/{id}', name: 'aparatura_pomiarowa_equipment_set_show', requirements: ['id' => '\d+'])]
    public function show(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie czy użytkownik może widzieć ten zestaw
        if (!$this->canUserViewEquipmentSet($user, $equipmentSet)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego zestawu.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canTransfer = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');

        // Pobranie powiązanych danych
        $reviews = $this->aparaturaService->getReviewsForEquipmentSet($equipmentSet);
        $transfers = $this->aparaturaService->getTransfersForEquipmentSet($equipmentSet);
        $activeTransfer = $this->aparaturaService->getActiveTransferForEquipmentSet($equipmentSet);

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_equipment_set', [
            'set_id' => $equipmentSet->getId(),
            'set_name' => $equipmentSet->getName()
        ], $request);
        
        return $this->render('aparatura-pomiarowa/equipment-set/show.html.twig', [
            'equipment_set' => $equipmentSet,
            'reviews' => $reviews,
            'transfers' => $transfers,
            'active_transfer' => $activeTransfer,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
            'can_transfer' => $canTransfer,
        ]);
    }

    #[Route('/{id}/edit', name: 'aparatura_pomiarowa_equipment_set_edit', requirements: ['id' => '\d+'])]
    public function edit(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        $form = $this->createForm(AparaturaPomiarowaEquipmentSetType::class, $equipmentSet, [
            'include_submit' => false
        ]);
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

                $this->aparaturaService->updateEquipmentSet($equipmentSet, $data, $user);
                
                $this->addFlash('success', 'Zestaw aparatury pomiarowej został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to update aparatura pomiarowa equipment set', [
                    'set_id' => $equipmentSet->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/equipment-set/form.html.twig', [
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'mode' => 'edit'
        ]);
    }

    #[Route('/{id}/delete', name: 'aparatura_pomiarowa_equipment_set_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('delete_equipment_set_' . $equipmentSet->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        try {
            $this->aparaturaService->deleteEquipmentSet($equipmentSet, $user);
            $this->addFlash('success', 'Zestaw aparatury pomiarowej został usunięty pomyślnie.');
            
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_index');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania zestawu.');
            $this->logger->error('Equipment set deletion error', [
                'set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/assign', name: 'aparatura_pomiarowa_equipment_set_assign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function assign(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('assign_equipment_set_' . $equipmentSet->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        $assignToUserId = $request->get('assign_to_user_id');
        if (!$assignToUserId) {
            $this->addFlash('error', 'Nie wybrano użytkownika do przypisania.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        try {
            $assignToUser = $this->entityManager->getRepository(User::class)->find($assignToUserId);
            if (!$assignToUser) {
                $this->addFlash('error', 'Wybrany użytkownik nie istnieje.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
            }

            $this->aparaturaService->assignEquipmentSetToUser($equipmentSet, $assignToUser, $user);
            $this->addFlash('success', 'Zestaw aparatury pomiarowej został przypisany do użytkownika ' . $assignToUser->getFullName());
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przypisywania zestawu.');
            $this->logger->error('Equipment set assignment error', [
                'set_id' => $equipmentSet->getId(),
                'assign_to' => $assignToUserId,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/unassign', name: 'aparatura_pomiarowa_equipment_set_unassign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unassign(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('unassign_equipment_set_' . $equipmentSet->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        try {
            $this->aparaturaService->unassignEquipmentSetFromUser($equipmentSet, $user);
            $this->addFlash('success', 'Zestaw aparatury pomiarowej został odłączony od użytkownika.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas odłączania zestawu.');
            $this->logger->error('Equipment set unassignment error', [
                'set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/add-equipment', name: 'aparatura_pomiarowa_equipment_set_add_equipment', requirements: ['id' => '\d+'])]
    public function addEquipmentForm(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        // Jeśli to POST, przekieruj do metody dodawania
        if ($request->isMethod('POST')) {
            return $this->processAddEquipment($equipmentSet, $request);
        }
        
        // Pobranie dostępnego sprzętu (nie przypisanego do żadnego zestawu) 
        $availableEquipment = $this->aparaturaService->getAvailableEquipmentForSet();
        
        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_add_equipment_form', [
            'equipment_set_id' => $equipmentSet->getId(),
            'available_equipment_count' => count($availableEquipment)
        ], $request);
        
        return $this->render('aparatura-pomiarowa/equipment-set/add-equipment.html.twig', [
            'equipment_set' => $equipmentSet,
            'available_equipment' => $availableEquipment
        ]);
    }

    private function processAddEquipment(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('add_equipment_' . $equipmentSet->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        $equipmentIds = $request->get('equipment_ids', []);
        if (empty($equipmentIds)) {
            $this->addFlash('error', 'Nie wybrano urządzenia do dodania.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        try {
            $addedCount = $this->aparaturaService->addEquipmentToSet($equipmentSet, $equipmentIds, $user);
            $this->addFlash('success', "Dodano {$addedCount} urządzenia do zestawu.");
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas dodawania urządzeń.');
            $this->logger->error('Add equipment to set error', [
                'set_id' => $equipmentSet->getId(),
                'equipment_ids' => $equipmentIds,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/remove-equipment', name: 'aparatura_pomiarowa_equipment_set_remove_equipment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeEquipment(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('remove_equipment_' . $equipmentSet->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        $equipmentIds = $request->get('equipment_ids', []);
        if (empty($equipmentIds)) {
            $this->addFlash('error', 'Nie wybrano urządzenia do usunięcia.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        try {
            $removedCount = $this->aparaturaService->removeEquipmentFromSet($equipmentSet, $equipmentIds, $user);
            $this->addFlash('success', "Usunięto {$removedCount} urządzenia z zestawu.");
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania urządzeń.');
            $this->logger->error('Remove equipment from set error', [
                'set_id' => $equipmentSet->getId(),
                'equipment_ids' => $equipmentIds,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/transfer', name: 'aparatura_pomiarowa_equipment_set_transfer', requirements: ['id' => '\d+'])]
    public function transfer(AparaturaPomiarowaEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        if (!$equipmentSet->isAvailable()) {
            $this->addFlash('error', 'Zestaw nie jest dostępny do przekazania.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()]);
        }

        $transfer = new AparaturaPomiarowaTransfer();
        $form = $this->createForm(AparaturaPomiarowaTransferType::class, $transfer, [
            'equipment_set' => $equipmentSet,
            'include_submit' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = [
                    'recipient' => $form->get('recipient')->getData(),
                    'transfer_date' => $form->get('transferDate')->getData(),
                    'return_date' => $form->get('returnDate')->getData(),
                    'purpose' => $form->get('purpose')->getData(),
                    'conditions' => $form->get('conditions')->getData(),
                    'location' => $form->get('location')->getData(),
                    'notes' => $form->get('notes')->getData()
                ];

                $selectedEquipmentIds = $request->get('selected_equipment_ids', []);
                
                $transfer = $this->transferService->createEquipmentSetTransfer($equipmentSet, $data, $user, $selectedEquipmentIds);
                
                $this->addFlash('success', 'Przekazanie zestawu aparatury pomiarowej zostało utworzone pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas tworzenia przekazania.');
                $this->logger->error('Equipment set transfer creation error', [
                    'set_id' => $equipmentSet->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }

        return $this->render('aparatura-pomiarowa/equipment-set/transfer.html.twig', [
            'form' => $form,
            'equipment_set' => $equipmentSet,
        ]);
    }

    #[Route('/search', name: 'aparatura_pomiarowa_equipment_set_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $query = $request->query->get('q', '');
        $limit = min($request->query->getInt('limit', 10), 50);
        
        if (strlen($query) < 2) {
            return new JsonResponse([]);
        }
        
        try {
            $results = $this->aparaturaService->searchEquipmentSets($query, $limit);
            
            $response = array_map(function($equipmentSet) {
                return [
                    'id' => $equipmentSet->getId(),
                    'name' => $equipmentSet->getName(),
                    'status' => $equipmentSet->getStatusDisplayName(),
                    'type' => $equipmentSet->getSetType(),
                    'equipment_count' => $equipmentSet->getEquipment()->count(),
                    'url' => $this->generateUrl('aparatura_pomiarowa_equipment_set_show', ['id' => $equipmentSet->getId()])
                ];
            }, $results);
            
            return new JsonResponse($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Equipment set search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd podczas wyszukiwania'], 500);
        }
    }

    private function canUserViewEquipmentSet(User $user, AparaturaPomiarowaEquipmentSet $equipmentSet): bool
    {
        // Administratorzy i edytorzy mogą widzieć wszystkie zestawy
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT')) {
            return true;
        }

        // Przeglądający mogą widzieć wszystkie zestawy
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW')) {
            return true;
        }

        // Użytkownicy z uprawnieniem VIEW_LIST mogą widzieć tylko przypisane do siebie zestawy
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW_LIST')) {
            return $equipmentSet->getAssignedTo() === $user;
        }

        return false;
    }
}