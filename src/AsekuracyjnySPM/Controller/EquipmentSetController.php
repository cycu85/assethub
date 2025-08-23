<?php

namespace App\AsekuracyjnySPM\Controller;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
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