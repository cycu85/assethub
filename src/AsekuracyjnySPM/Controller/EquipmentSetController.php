<?php

namespace App\AsekuracyjnySPM\Controller;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyTransfer;
use App\AsekuracyjnySPM\Service\AsekuracyjnyService;
use App\AsekuracyjnySPM\Form\AsekuracyjnyEquipmentSetType;
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

#[Route('/asekuracja/equipment-sets')]
class EquipmentSetController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AsekuracyjnyService $asekuracyjnyService,
        private EntityManagerInterface $entityManager,
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
            'overdue_review' => $request->query->getBoolean('overdue_review'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $equipmentSetsPagination = $this->asekuracyjnyService->getEquipmentSetsWithPagination($page, 1000, $filters);
        $statistics = $this->asekuracyjnyService->getEquipmentSetStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'asekuracja', 'CREATE');
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'asekuracja', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');

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
            'can_review' => $canReview,
        ]);
    }

    #[Route('/new', name: 'asekuracja_equipment_set_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'CREATE', $request);
        
        $equipmentSet = new AsekuracyjnyEquipmentSet();
        $form = $this->createForm(AsekuracyjnyEquipmentSetType::class, $equipmentSet, [
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

        // Get active users for transfer modal
        $users = $this->entityManager->getRepository(User::class)->findBy(['isActive' => true]);

        // Get active reviews for this equipment set
        $reviewRepository = $this->entityManager->getRepository(\App\AsekuracyjnySPM\Entity\AsekuracyjnyReview::class);
        $activeReviews = $reviewRepository->getActiveReviewsForEquipmentSet($equipmentSet);

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
            'users' => $users,
            'active_reviews' => $activeReviews,
        ]);
    }

    #[Route('/{id}/edit', name: 'asekuracja_equipment_set_edit', requirements: ['id' => '\d+'])]
    public function edit(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        $form = $this->createForm(AsekuracyjnyEquipmentSetType::class, $equipmentSet, [
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
        
        // Pobranie dostępnego sprzętu (nie przypisanego do żadnego zestawu)
        $availableEquipment = $this->asekuracyjnyService->getAvailableEquipmentForSet();
        
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

    #[Route('/{id}/equipment/remove-bulk', name: 'asekuracja_equipment_set_remove_bulk_equipment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function removeBulkEquipment(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('remove_bulk_equipment_' . $equipmentSet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        $equipmentIds = $request->request->all('equipment_ids');
        
        if (empty($equipmentIds)) {
            $this->addFlash('error', 'Nie wybrano żadnego sprzętu do usunięcia.');
            return $this->redirectToRoute('asekuracja_equipment_set_add_equipment', ['id' => $equipmentSet->getId()]);
        }
        
        $removedCount = 0;
        $errors = [];
        
        try {
            $this->entityManager->beginTransaction();
            
            foreach ($equipmentIds as $equipmentId) {
                try {
                    $equipment = $this->asekuracyjnyService->getEquipmentRepository()->find($equipmentId);
                    if (!$equipment) {
                        $errors[] = "Sprzęt o ID {$equipmentId} nie został znaleziony.";
                        continue;
                    }
                    
                    $this->asekuracyjnyService->removeEquipmentFromSet($equipmentSet, $equipment, $user);
                    $removedCount++;
                    
                } catch (BusinessLogicException $e) {
                    $errors[] = sprintf('Błąd przy usuwaniu "%s": %s', $equipment->getName() ?? "ID {$equipmentId}", $e->getMessage());
                } catch (\Exception $e) {
                    $errors[] = sprintf('Nieoczekiwany błąd przy usuwaniu sprzętu ID %s', $equipmentId);
                    $this->logger->error('Failed to remove equipment from set in bulk operation', [
                        'equipment_set_id' => $equipmentSet->getId(),
                        'equipment_id' => $equipmentId,
                        'error' => $e->getMessage(),
                        'user' => $user->getUsername()
                    ]);
                }
            }
            
            $this->entityManager->commit();
            
            // Flash messages
            if ($removedCount > 0) {
                $message = sprintf('Usunięto %d %s z zestawu.', 
                    $removedCount, 
                    $removedCount === 1 ? 'element' : ($removedCount < 5 ? 'elementy' : 'elementów')
                );
                $this->addFlash('success', $message);
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania sprzętu. Operacja została wycofana.');
            $this->logger->error('Bulk equipment removal failed', [
                'equipment_set_id' => $equipmentSet->getId(),
                'equipment_ids' => $equipmentIds,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_add_equipment', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/available-equipment', name: 'asekuracja_available_equipment_modal')]
    public function availableEquipmentModal(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        $limit = 1000;
        
        try {
            // Pobranie sprzętu dostępnego dla zestawów (nie przypisanego do żadnego zestawu)
            $availableEquipment = $this->asekuracyjnyService->getAvailableEquipmentForSet();
            
            // Filtrowanie po wyszukiwaniu
            if (!empty($search)) {
                $availableEquipment = array_filter($availableEquipment, function($equipment) use ($search) {
                    $searchLower = strtolower($search);
                    return stripos($equipment->getName(), $search) !== false ||
                           stripos($equipment->getInventoryNumber(), $search) !== false ||
                           stripos($equipment->getModel(), $search) !== false ||
                           stripos($equipment->getManufacturer(), $search) !== false;
                });
            }
            
            // Paginacja ręczna
            $total = count($availableEquipment);
            $offset = ($page - 1) * $limit;
            $items = array_slice($availableEquipment, $offset, $limit);
            
            $pagination = [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ];
            
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

    // === ATTACHMENT MANAGEMENT ===

    #[Route('/{id}/attachment/upload', name: 'asekuracja_equipment_set_attachment_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadAttachment(AsekuracyjnyEquipmentSet $equipmentSet, Request $request): Response
    {
        $user = $this->getUser();
        
        // Debug logging
        $this->logger->info('Upload attachment called', [
            'equipment_set_id' => $equipmentSet->getId(),
            'user' => $user->getUsername(),
            'files' => $request->files->count(),
            'method' => $request->getMethod()
        ]);
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('upload_equipment_set_attachment_' . $equipmentSet->getId(), $request->request->get('_token'))) {
            $this->logger->warning('Invalid CSRF token for attachment upload', [
                'equipment_set_id' => $equipmentSet->getId(),
                'user' => $user->getUsername()
            ]);
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Ensure entity is managed by entity manager
            $equipmentSet = $this->entityManager->find(AsekuracyjnyEquipmentSet::class, $equipmentSet->getId());
            if (!$equipmentSet) {
                throw new \RuntimeException('Equipment set not found');
            }
            
            $uploadedFiles = $request->files->get('attachments', []);
            $description = $request->request->get('description', '');
            
            $this->logger->info('Processing attachment upload', [
                'equipment_set_id' => $equipmentSet->getId(),
                'files_count' => count($uploadedFiles),
                'description' => $description
            ]);
            
            if (empty($uploadedFiles)) {
                $this->addFlash('error', 'Nie wybrano żadnych plików do przesłania.');
                return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
            }
            
            $uploadedCount = 0;
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/equipment-sets/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
                $this->logger->info('Created upload directory', ['path' => $uploadDir]);
            }
            
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
                    $this->logger->info('Processing file', [
                        'filename' => $uploadedFile->getClientOriginalName(),
                        'size' => $uploadedFile->getSize(),
                        'mime_type' => $uploadedFile->getMimeType()
                    ]);
                    
                    // Validate file size (10MB max)
                    if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
                        $this->addFlash('warning', sprintf('Plik "%s" jest za duży (max 10MB).', $uploadedFile->getClientOriginalName()));
                        continue;
                    }
                    
                    // Validate file type
                    $allowedMimeTypes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'image/jpeg',
                        'image/png',
                        'text/plain'
                    ];
                    
                    if (!in_array($uploadedFile->getMimeType(), $allowedMimeTypes)) {
                        $this->addFlash('warning', sprintf('Typ pliku "%s" nie jest dozwolony.', $uploadedFile->getClientOriginalName()));
                        $this->logger->warning('File type not allowed', [
                            'filename' => $uploadedFile->getClientOriginalName(),
                            'mime_type' => $uploadedFile->getMimeType()
                        ]);
                        continue;
                    }
                    
                    // Generate unique filename and get file info before moving
                    $filename = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
                    $originalName = $uploadedFile->getClientOriginalName();
                    $fileSize = $uploadedFile->getSize();
                    $mimeType = $uploadedFile->getMimeType();
                    
                    $uploadedFile->move($uploadDir, $filename);
                    
                    $this->logger->info('File moved successfully', [
                        'original_name' => $originalName,
                        'new_filename' => $filename,
                        'path' => $uploadDir . $filename
                    ]);
                    
                    // Add to equipment set attachments
                    $attachmentData = [
                        'filename' => $filename,
                        'original_name' => $originalName,
                        'size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'uploaded_by' => $user->getFullName(),
                        'description' => $description
                    ];
                    
                    $equipmentSet->addAttachment($attachmentData);
                    $uploadedCount++;
                    
                    $this->logger->info('Attachment added to entity', [
                        'attachment_data' => $attachmentData,
                        'current_attachments_count' => count($equipmentSet->getAttachments())
                    ]);
                } else {
                    $this->logger->warning('Invalid uploaded file', [
                        'file_error' => $uploadedFile ? $uploadedFile->getError() : 'File is null'
                    ]);
                }
            }
            
            if ($uploadedCount > 0) {
                // Set updated by and updated at
                $equipmentSet->setUpdatedBy($user);
                $equipmentSet->setUpdatedAt(new \DateTime());
                
                $this->entityManager->persist($equipmentSet);
                $this->entityManager->flush();
                
                $this->logger->info('Attachments persisted to database', [
                    'equipment_set_id' => $equipmentSet->getId(),
                    'uploaded_count' => $uploadedCount,
                    'total_attachments' => count($equipmentSet->getAttachments())
                ]);
                
                $this->addFlash('success', sprintf('Przesłano %d załączników pomyślnie.', $uploadedCount));
                
                // Audit
                $this->auditService->logUserAction($user, 'upload_equipment_set_attachment', [
                    'equipment_set_id' => $equipmentSet->getId(),
                    'files_count' => $uploadedCount,
                    'description' => $description
                ], $request);
            } else {
                $this->logger->warning('No files were uploaded successfully');
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas przesyłania załączników.');
            $this->logger->error('Failed to upload equipment set attachment', [
                'equipment_set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/{id}/attachment/{filename}/download', name: 'asekuracja_equipment_set_attachment_download', requirements: ['id' => '\d+'])]
    public function downloadAttachment(AsekuracyjnyEquipmentSet $equipmentSet, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        // Check if user can view this equipment set
        if (!$this->canUserViewEquipmentSet($user, $equipmentSet)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego zestawu.');
        }
        
        // Find attachment
        $attachment = null;
        foreach ($equipmentSet->getAttachments() as $att) {
            if ($att['filename'] === $filename) {
                $attachment = $att;
                break;
            }
        }
        
        if (!$attachment) {
            throw $this->createNotFoundException('Załącznik nie został znaleziony.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/equipment-sets/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik nie został znaleziony na serwerze.');
        }
        
        // Audit
        $this->auditService->logUserAction($user, 'download_equipment_set_attachment', [
            'equipment_set_id' => $equipmentSet->getId(),
            'filename' => $filename,
            'original_name' => $attachment['original_name']
        ], $request);
        
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $attachment['original_name']);
        
        return $response;
    }

    #[Route('/{id}/attachment/{filename}/delete', name: 'asekuracja_equipment_set_attachment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteAttachment(AsekuracyjnyEquipmentSet $equipmentSet, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_attachment_' . $equipmentSet->getId() . '_' . $filename, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Find and remove attachment from database
            $attachment = null;
            foreach ($equipmentSet->getAttachments() as $att) {
                if ($att['filename'] === $filename) {
                    $attachment = $att;
                    break;
                }
            }
            
            if (!$attachment) {
                $this->addFlash('error', 'Załącznik nie został znaleziony.');
                return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
            }
            
            $equipmentSet->removeAttachment($filename);
            $this->entityManager->flush();
            
            // Remove file from filesystem
            $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/equipment-sets/' . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->addFlash('success', sprintf('Załącznik "%s" został usunięty.', $attachment['original_name']));
            
            // Audit
            $this->auditService->logUserAction($user, 'delete_equipment_set_attachment', [
                'equipment_set_id' => $equipmentSet->getId(),
                'filename' => $filename,
                'original_name' => $attachment['original_name']
            ], $request);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania załącznika.');
            $this->logger->error('Failed to delete equipment set attachment', [
                'equipment_set_id' => $equipmentSet->getId(),
                'filename' => $filename,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    // === TRANSFER MANAGEMENT ===

    #[Route('/transfer/{setId}/prepare', name: 'asekuracja_transfer_prepare', requirements: ['setId' => '\d+'], methods: ['POST'])]
    public function prepareTransfer(int $setId, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'TRANSFER', $request);
        
        $equipmentSet = $this->entityManager->find(AsekuracyjnyEquipmentSet::class, $setId);
        if (!$equipmentSet) {
            throw $this->createNotFoundException('Zestaw nie został znaleziony.');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('prepare_transfer_' . $equipmentSet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $recipientId = $request->request->get('recipient_id');
            $transferDate = $request->request->get('transfer_date');
            $purpose = $request->request->get('purpose');
            $notes = $request->request->get('notes');
            
            // Validate recipient
            $recipient = $this->entityManager->find(User::class, $recipientId);
            if (!$recipient) {
                throw new BusinessLogicException('Wybrany odbiorca nie został znaleziony.');
            }
            
            // Create transfer
            $transfer = new AsekuracyjnyTransfer();
            $transfer->setEquipmentSet($equipmentSet)
                    ->setRecipient($recipient)
                    ->setTransferDate(new \DateTime($transferDate))
                    ->setPurpose($purpose)
                    ->setNotes($notes)
                    ->setHandedBy($user)
                    ->setCreatedBy($user);
            
            // Start the transfer (set to "W trakcie")
            $transfer->startTransfer();
            
            $this->entityManager->persist($transfer);
            $this->entityManager->flush();
            
            // Generate PDF protocol
            try {
                $pdfContent = $this->generateTransferProtocolPDF($transfer);
                $filename = 'protocol_' . $transfer->getTransferNumber() . '.pdf';
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                file_put_contents($uploadDir . $filename, $pdfContent);
                $transfer->setProtocolScanFilename($filename);
                $this->entityManager->flush();
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to generate transfer protocol PDF', [
                    'transfer_id' => $transfer->getId(),
                    'error' => $e->getMessage()
                ]);
                // Don't fail the transfer if PDF generation fails
            }
            
            $this->addFlash('success', sprintf(
                'Przekazanie %s zostało przygotowane. Status: %s', 
                $transfer->getTransferNumber(),
                $transfer->getStatusDisplayName()
            ));
            
            // Audit
            $this->auditService->logUserAction($user, 'prepare_equipment_set_transfer', [
                'equipment_set_id' => $equipmentSet->getId(),
                'transfer_id' => $transfer->getId(),
                'recipient_id' => $recipient->getId(),
                'transfer_date' => $transferDate
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przygotowywania przekazania.');
            $this->logger->error('Failed to prepare equipment set transfer', [
                'equipment_set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/transfer/{id}/complete', name: 'asekuracja_transfer_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeTransfer(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'TRANSFER', $request);
        
        $transfer = $this->entityManager->find(AsekuracyjnyTransfer::class, $id);
        if (!$transfer) {
            throw $this->createNotFoundException('Przekazanie nie zostało znalezione.');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('complete_transfer', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            if (!$transfer->canBeCompleted()) {
                throw new BusinessLogicException('Przekazanie nie może być zakończone w aktualnym stanie.');
            }
            
            $protocolFile = $request->files->get('protocol_file');
            if (!$protocolFile || !$protocolFile->isValid()) {
                throw new BusinessLogicException('Wymagany jest poprawny plik protokołu PDF.');
            }
            
            // Validate file type
            if ($protocolFile->getMimeType() !== 'application/pdf') {
                throw new BusinessLogicException('Protokół musi być w formacie PDF.');
            }
            
            // Validate file size (10MB max)
            if ($protocolFile->getSize() > 10 * 1024 * 1024) {
                throw new BusinessLogicException('Plik protokołu jest za duży (maksymalnie 10MB).');
            }
            
            // Upload protocol file
            $filename = 'signed_protocol_' . $transfer->getTransferNumber() . '_' . uniqid() . '.pdf';
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $protocolFile->move($uploadDir, $filename);
            
            // Complete transfer and assign equipment set
            $transfer->uploadProtocolScan($filename, $user);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Przekazanie %s zostało zakończone. Zestaw został przypisany do odbiorcy.', 
                $transfer->getTransferNumber()
            ));
            
            // Audit
            $this->auditService->logUserAction($user, 'complete_equipment_set_transfer', [
                'transfer_id' => $transfer->getId(),
                'equipment_set_id' => $transfer->getEquipmentSet()->getId(),
                'recipient_id' => $transfer->getRecipient()->getId(),
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas kończenia przekazania.');
            $this->logger->error('Failed to complete equipment set transfer', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $transfer->getEquipmentSet()->getId()]);
    }

    #[Route('/transfer/{id}/protocol/download', name: 'asekuracja_transfer_protocol_download', requirements: ['id' => '\d+'])]
    public function downloadTransferProtocol(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $transfer = $this->entityManager->find(AsekuracyjnyTransfer::class, $id);
        if (!$transfer) {
            throw $this->createNotFoundException('Przekazanie nie zostało znalezione.');
        }
        
        // Check if user can view this transfer
        if (!$this->canUserViewEquipmentSet($user, $transfer->getEquipmentSet())) {
            throw $this->createAccessDeniedException('Brak uprawnień do pobrania tego protokołu.');
        }
        
        if (!$transfer->hasProtocolScan()) {
            throw $this->createNotFoundException('Protokół nie został jeszcze wygenerowany lub przesłany.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/' . $transfer->getProtocolScanFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik protokołu nie został znaleziony na serwerze.');
        }
        
        // Audit
        $this->auditService->logUserAction($user, 'download_transfer_protocol', [
            'transfer_id' => $transfer->getId(),
            'equipment_set_id' => $transfer->getEquipmentSet()->getId(),
            'filename' => $transfer->getProtocolScanFilename()
        ], $request);
        
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, 
            'protokol_' . $transfer->getTransferNumber() . '.pdf'
        );
        
        return $response;
    }

    // === RETURN MANAGEMENT ===

    #[Route('/return/{setId}/prepare', name: 'asekuracja_return_prepare', requirements: ['setId' => '\d+'], methods: ['POST'])]
    public function prepareReturn(int $setId, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'TRANSFER', $request);
        
        $equipmentSet = $this->entityManager->find(AsekuracyjnyEquipmentSet::class, $setId);
        if (!$equipmentSet) {
            throw $this->createNotFoundException('Zestaw nie został znaleziony.');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('prepare_return_' . $equipmentSet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Find active transfer for this equipment set
            $activeTransfer = null;
            foreach ($equipmentSet->getTransfers() as $transfer) {
                if ($transfer->isTransferred()) {
                    $activeTransfer = $transfer;
                    break;
                }
            }
            
            if (!$activeTransfer) {
                throw new BusinessLogicException('Nie znaleziono aktywnego przekazania dla tego zestawu.');
            }
            
            $returnDate = $request->request->get('return_date');
            $returnNotes = $request->request->get('return_notes');
            
            // Start return process
            $activeTransfer->startReturn($returnNotes);
            
            $this->entityManager->flush();
            
            // Generate return protocol PDF
            try {
                $pdfContent = $this->generateReturnProtocolPDF($activeTransfer);
                $filename = 'return_protocol_' . $activeTransfer->getTransferNumber() . '.pdf';
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                file_put_contents($uploadDir . $filename, $pdfContent);
                $activeTransfer->setReturnProtocolFilename($filename);
                $this->entityManager->flush();
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to generate return protocol PDF', [
                    'transfer_id' => $activeTransfer->getId(),
                    'error' => $e->getMessage()
                ]);
                // Don't fail the return if PDF generation fails
            }
            
            $this->addFlash('success', sprintf(
                'Zwrot %s został przygotowany. Status: %s', 
                $activeTransfer->getTransferNumber(),
                $activeTransfer->getStatusDisplayName()
            ));
            
            // Audit
            $this->auditService->logUserAction($user, 'prepare_equipment_set_return', [
                'equipment_set_id' => $equipmentSet->getId(),
                'transfer_id' => $activeTransfer->getId(),
                'return_date' => $returnDate
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przygotowywania zwrotu.');
            $this->logger->error('Failed to prepare equipment set return', [
                'equipment_set_id' => $equipmentSet->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $equipmentSet->getId()]);
    }

    #[Route('/transfer/{id}/return', name: 'asekuracja_transfer_return', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function prepareReturnForTransfer(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'TRANSFER', $request);
        
        $transfer = $this->entityManager->find(AsekuracyjnyTransfer::class, $id);
        if (!$transfer) {
            throw $this->createNotFoundException('Przekazanie nie zostało znalezione.');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('prepare_return', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            if (!$transfer->canBeReturned()) {
                throw new BusinessLogicException('Zwrot nie może być przygotowany w aktualnym stanie.');
            }
            
            $returnDate = $request->request->get('return_date');
            $returnNotes = $request->request->get('return_notes');
            
            // Start return process
            $transfer->startReturn($returnNotes);
            
            $this->entityManager->flush();
            
            // Generate return protocol PDF
            try {
                $pdfContent = $this->generateReturnProtocolPDF($transfer);
                $filename = 'return_protocol_' . $transfer->getTransferNumber() . '.pdf';
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                file_put_contents($uploadDir . $filename, $pdfContent);
                $transfer->setReturnProtocolFilename($filename);
                $this->entityManager->flush();
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to generate return protocol PDF', [
                    'transfer_id' => $transfer->getId(),
                    'error' => $e->getMessage()
                ]);
                // Don't fail the return if PDF generation fails
            }
            
            $this->addFlash('success', sprintf(
                'Zwrot %s został przygotowany. Status: %s', 
                $transfer->getTransferNumber(),
                $transfer->getStatusDisplayName()
            ));
            
            // Audit
            $this->auditService->logUserAction($user, 'prepare_transfer_return', [
                'transfer_id' => $transfer->getId(),
                'equipment_set_id' => $transfer->getEquipmentSet()->getId(),
                'return_date' => $returnDate
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przygotowywania zwrotu.');
            $this->logger->error('Failed to prepare transfer return', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $transfer->getEquipmentSet()->getId()]);
    }

    #[Route('/return/{id}/complete', name: 'asekuracja_return_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeReturn(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'TRANSFER', $request);
        
        $transfer = $this->entityManager->find(AsekuracyjnyTransfer::class, $id);
        if (!$transfer) {
            throw $this->createNotFoundException('Zwrot nie został znaleziony.');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('complete_return', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            if (!$transfer->canReturnBeCompleted()) {
                throw new BusinessLogicException('Zwrot nie może być zakończony w aktualnym stanie.');
            }
            
            $returnProtocolFile = $request->files->get('return_protocol_file');
            if (!$returnProtocolFile || !$returnProtocolFile->isValid()) {
                throw new BusinessLogicException('Wymagany jest poprawny plik protokołu zwrotu PDF.');
            }
            
            // Validate file type
            if ($returnProtocolFile->getMimeType() !== 'application/pdf') {
                throw new BusinessLogicException('Protokół zwrotu musi być w formacie PDF.');
            }
            
            // Validate file size (10MB max)
            if ($returnProtocolFile->getSize() > 10 * 1024 * 1024) {
                throw new BusinessLogicException('Plik protokołu zwrotu jest za duży (maksymalnie 10MB).');
            }
            
            // Upload return protocol file
            $filename = 'signed_return_protocol_' . $transfer->getTransferNumber() . '_' . uniqid() . '.pdf';
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $returnProtocolFile->move($uploadDir, $filename);
            
            // Complete return and unassign equipment set
            $transfer->completeReturn($user, $filename);
            
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Zwrot %s został zakończony. Zestaw został odłączony od użytkownika.', 
                $transfer->getTransferNumber()
            ));
            
            // Audit
            $this->auditService->logUserAction($user, 'complete_equipment_set_return', [
                'transfer_id' => $transfer->getId(),
                'equipment_set_id' => $transfer->getEquipmentSet()->getId(),
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas kończenia zwrotu.');
            $this->logger->error('Failed to complete equipment set return', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('asekuracja_equipment_set_show', ['id' => $transfer->getEquipmentSet()->getId()]);
    }

    #[Route('/return/{id}/protocol/download', name: 'asekuracja_return_protocol_download', requirements: ['id' => '\d+'])]
    public function downloadReturnProtocol(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $transfer = $this->entityManager->find(AsekuracyjnyTransfer::class, $id);
        if (!$transfer) {
            throw $this->createNotFoundException('Zwrot nie został znaleziony.');
        }
        
        // Check if user can view this transfer
        if (!$this->canUserViewEquipmentSet($user, $transfer->getEquipmentSet())) {
            throw $this->createAccessDeniedException('Brak uprawnień do pobrania tego protokołu zwrotu.');
        }
        
        if (!$transfer->hasReturnProtocolScan()) {
            throw $this->createNotFoundException('Protokół zwrotu nie został jeszcze wygenerowany lub przesłany.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/asekuracja/transfers/' . $transfer->getReturnProtocolFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik protokołu zwrotu nie został znaleziony na serwerze.');
        }
        
        // Audit
        $this->auditService->logUserAction($user, 'download_return_protocol', [
            'transfer_id' => $transfer->getId(),
            'equipment_set_id' => $transfer->getEquipmentSet()->getId(),
            'filename' => $transfer->getReturnProtocolFilename()
        ], $request);
        
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, 
            'protokol_zwrotu_' . $transfer->getTransferNumber() . '.pdf'
        );
        
        return $response;
    }

    private function generateReturnProtocolPDF(AsekuracyjnyTransfer $transfer): string
    {
        $equipmentSet = $transfer->getEquipmentSet();
        $recipient = $transfer->getRecipient();
        $handedBy = $transfer->getHandedBy();
        
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('AssetHub System');
        $pdf->SetAuthor('AssetHub');
        $pdf->SetTitle('Protokół zwrotu zestawu asekuracyjnego');
        $pdf->SetSubject('Protokół zwrotu - ' . $transfer->getTransferNumber());
        
        // Set margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Title
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 15, 'PROTOKÓŁ ZWROTU ZESTAWU ASEKURACYJNEGO', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Protocol number and date
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Numer protokołu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferNumber() . ' - ZWROT', 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data zwrotu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, (new \DateTime())->format('d.m.Y'), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data przekazania:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferDate()->format('d.m.Y'), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Equipment set information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'DANE ZESTAWU', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipmentSet->getName(), 0, 1, 'L');
        
        if ($equipmentSet->getSetType()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Typ:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getSetType(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Return information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ZWRACAJĄCY', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Receiving back information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'PRZYJMUJĄCY ZWROT', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Equipment list
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ELEMENTY ZESTAWU', 0, 1, 'L');
        
        // Table header
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(10, 8, 'Lp.', 1, 0, 'C');
        $pdf->Cell(60, 8, 'Nazwa', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Numer inwentarzowy', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Typ', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Producent', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Status', 1, 1, 'C');
        
        // Equipment items
        $pdf->SetFont('dejavusans', '', 8);
        $counter = 1;
        foreach ($equipmentSet->getEquipment() as $equipment) {
            $pdf->Cell(10, 6, $counter++, 1, 0, 'C');
            $pdf->Cell(60, 6, $equipment->getName(), 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getInventoryNumber(), 1, 0, 'C');
            $pdf->Cell(25, 6, $equipment->getEquipmentType() ?? '', 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getManufacturer() ?? '', 1, 0, 'L');
            $pdf->Cell(20, 6, $equipment->getStatusDisplayName(), 1, 1, 'C');
        }
        $pdf->Ln(5);
        
        // Return notes
        if ($transfer->getReturnNotes()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Uwagi dotyczące zwrotu:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getReturnNotes(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Signatures
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(90, 8, 'Podpis zwracającego:', 0, 0, 'L');
        $pdf->Cell(90, 8, 'Podpis przyjmującego:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 6, '_________________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_________________________________', 0, 1, 'C');
        $pdf->Cell(90, 6, $recipient->getFullName(), 0, 0, 'C');
        $pdf->Cell(90, 6, $handedBy->getFullName(), 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->Cell(0, 4, 'Data i miejsce:', 0, 1, 'L');
        $pdf->Cell(0, 4, '_________________________________', 0, 1, 'L');
        
        // Return PDF as string
        return $pdf->Output('', 'S');
    }

    private function generateTransferProtocolPDF(AsekuracyjnyTransfer $transfer): string
    {
        $equipmentSet = $transfer->getEquipmentSet();
        $recipient = $transfer->getRecipient();
        $handedBy = $transfer->getHandedBy();
        
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('AssetHub System');
        $pdf->SetAuthor('AssetHub');
        $pdf->SetTitle('Protokół przekazania zestawu asekuracyjnego');
        $pdf->SetSubject('Protokół przekazania - ' . $transfer->getTransferNumber());
        
        // Set margins
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, 25);
        
        // Set font
        $pdf->SetFont('dejavusans', '', 10);
        
        // Add a page
        $pdf->AddPage();
        
        // Title
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 15, 'PROTOKÓŁ PRZEKAZANIA ZESTAWU ASEKURACYJNEGO', 0, 1, 'C');
        $pdf->Ln(5);
        
        // Protocol number and date
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Numer protokołu:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferNumber(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(60, 8, 'Data przekazania:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->Cell(0, 8, $transfer->getTransferDate()->format('d.m.Y'), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Equipment set information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'DANE ZESTAWU', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Nazwa:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $equipmentSet->getName(), 0, 1, 'L');
        
        if ($equipmentSet->getSetType()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Typ:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getSetType(), 0, 1, 'L');
        }
        
        if ($equipmentSet->getLocation()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Lokalizacja:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $equipmentSet->getLocation(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Recipient information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ODBIORCA', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $recipient->getEmail(), 0, 1, 'L');
        
        if ($recipient->getBranch()) {
            $pdf->SetFont('dejavusans', 'B', 11);
            $pdf->Cell(40, 6, 'Oddział:', 0, 0, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->Cell(0, 6, $recipient->getBranch(), 0, 1, 'L');
        }
        $pdf->Ln(5);
        
        // Handler information
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'PRZEKAZAŁ', 0, 1, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Imię i nazwisko:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getFullName(), 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
        $pdf->SetFont('dejavusans', '', 11);
        $pdf->Cell(0, 6, $handedBy->getEmail(), 0, 1, 'L');
        $pdf->Ln(5);
        
        // Equipment list
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, 'ELEMENTY ZESTAWU', 0, 1, 'L');
        
        // Table header
        $pdf->SetFont('dejavusans', 'B', 9);
        $pdf->Cell(10, 8, 'Lp.', 1, 0, 'C');
        $pdf->Cell(60, 8, 'Nazwa', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Numer inwentarzowy', 1, 0, 'C');
        $pdf->Cell(25, 8, 'Typ', 1, 0, 'C');
        $pdf->Cell(35, 8, 'Producent', 1, 0, 'C');
        $pdf->Cell(20, 8, 'Status', 1, 1, 'C');
        
        // Equipment items
        $pdf->SetFont('dejavusans', '', 8);
        $counter = 1;
        foreach ($equipmentSet->getEquipment() as $equipment) {
            $pdf->Cell(10, 6, $counter++, 1, 0, 'C');
            $pdf->Cell(60, 6, $equipment->getName(), 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getInventoryNumber(), 1, 0, 'C');
            $pdf->Cell(25, 6, $equipment->getEquipmentType() ?? '', 1, 0, 'L');
            $pdf->Cell(35, 6, $equipment->getManufacturer() ?? '', 1, 0, 'L');
            $pdf->Cell(20, 6, $equipment->getStatusDisplayName(), 1, 1, 'C');
        }
        $pdf->Ln(5);
        
        // Purpose and notes
        if ($transfer->getPurpose()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Cel przekazania:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getPurpose(), 0, 'L');
            $pdf->Ln(3);
        }
        
        if ($transfer->getNotes()) {
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 8, 'Uwagi:', 0, 1, 'L');
            $pdf->SetFont('dejavusans', '', 11);
            $pdf->MultiCell(0, 6, $transfer->getNotes(), 0, 'L');
            $pdf->Ln(3);
        }
        
        // Signatures
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(90, 8, 'Podpis odbiorcy:', 0, 0, 'L');
        $pdf->Cell(90, 8, 'Podpis przekazującego:', 0, 1, 'L');
        $pdf->Ln(5);
        
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(90, 6, '_________________________________', 0, 0, 'C');
        $pdf->Cell(90, 6, '_________________________________', 0, 1, 'C');
        $pdf->Cell(90, 6, $recipient->getFullName(), 0, 0, 'C');
        $pdf->Cell(90, 6, $handedBy->getFullName(), 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->Cell(0, 4, 'Data i miejsce:', 0, 1, 'L');
        $pdf->Cell(0, 4, '_________________________________', 0, 1, 'L');
        
        // Return PDF as string
        return $pdf->Output('', 'S');
    }

    // === PRIVATE HELPER METHODS ===

    private function canUserViewEquipmentSet(User $user, AsekuracyjnyEquipmentSet $equipmentSet): bool
    {
        // Admini i edytorzy mogą widzieć wszystko
        if ($this->authorizationService->checkAnyPermission($user, 'asekuracja', ['EDIT', 'DELETE', 'ASSIGN'])) {
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