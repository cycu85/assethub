<?php

namespace App\AparaturaPomiarowa\Controller;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaService;
use App\AparaturaPomiarowa\Form\AparaturaPomiarowaEquipmentType;
use App\Entity\User;
use App\Service\AuthorizationService;
use App\Service\AuditService;
use App\Exception\ValidationException;
use App\Exception\BusinessLogicException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/aparatura-pomiarowa')]
class AparaturaPomiarowaController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AparaturaPomiarowaService $aparaturaPomiarowaService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'aparatura_pomiarowa_index')]
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
            'equipment_type' => $request->query->get('equipment_type'),
            'equipment_set_id' => $request->query->get('equipment_set_id'),
            'assigned_to' => $request->query->get('assigned_to'),
            'needs_review' => $request->query->getBoolean('needs_review'),
            'overdue_review' => $request->query->getBoolean('overdue_review'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $equipmentPagination = $this->aparaturaPomiarowaService->getEquipmentWithPagination($page, 0, $filters);
        $statistics = $this->aparaturaPomiarowaService->getEquipmentStatistics();
        $allEquipmentSets = $this->aparaturaPomiarowaService->getAllEquipmentSets();
        
        // Sprawdzenie uprawnień do różnych akcji
        $canCreate = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'CREATE');
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_equipment_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_equipment' => $equipmentPagination['total']
        ], $request);
        
        return $this->render('aparatura-pomiarowa/equipment/index.html.twig', [
            'equipment' => $equipmentPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'all_equipment_sets' => $allEquipmentSets,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
        ]);
    }

    #[Route('/equipment/new', name: 'aparatura_pomiarowa_equipment_new')]
    public function newEquipment(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'CREATE', $request);
        
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

                $equipment = $this->aparaturaPomiarowaService->createEquipment($data, $user);
                
                $this->addFlash('success', 'Sprzęt aparaturaPomiarowa został utworzony pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to create aparaturaPomiarowa equipment', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/equipment/form.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
            'mode' => 'create'
        ]);
    }

    #[Route('/equipment/{id}', name: 'aparatura_pomiarowa_equipment_show', requirements: ['id' => '\d+'])]
    public function showEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie czy użytkownik może widzieć ten sprzęt
        if (!$this->canUserViewEquipment($user, $equipment)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego sprzętu.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT') 
                   || ($equipment->getAssignedTo() === $user && $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW'));
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canTransfer = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');
        
        // Użytkownicy z rolą VIEW_OWN nie mogą edytować, usuwać ani zarządzać
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW_OWN') && 
            !$this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT')) {
            $canEdit = false;
            $canDelete = false;
            $canAssign = false;
            $canTransfer = false;
        }

        // Pobierz przeglądy posortowane chronologicznie (najnowsze pierwsze)
        // 1. Przeglądy bezpośrednio tego sprzętu
        // 2. Przeglądy zestawów, w których uczestniczy ten sprzęt
        $reviews = $this->entityManager->getRepository(AsekuracyjnyReview::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.equipmentSet', 'es')
            ->leftJoin('es.equipment', 'eq')
            ->where('r.equipment = :equipment OR eq.id = :equipmentId')
            ->setParameter('equipment', $equipment)
            ->setParameter('equipmentId', $equipment->getId())
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_equipment', [
            'equipment_id' => $equipment->getId(),
            'equipment_name' => $equipment->getName()
        ], $request);
        
        return $this->render('aparatura-pomiarowa/equipment/show.html.twig', [
            'equipment' => $equipment,
            'reviews' => $reviews,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'can_assign' => $canAssign,
            'can_review' => $canReview,
            'can_transfer' => $canTransfer,
        ]);
    }

    #[Route('/equipment/{id}/edit', name: 'aparatura_pomiarowa_equipment_edit', requirements: ['id' => '\d+'])]
    public function editEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
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

                $this->aparaturaPomiarowaService->updateEquipment($equipment, $data, $user);
                
                $this->addFlash('success', 'Sprzęt aparaturaPomiarowa został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to update aparaturaPomiarowa equipment', [
                    'equipment_id' => $equipment->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/equipment/form.html.twig', [
            'form' => $form,
            'equipment' => $equipment,
            'mode' => 'edit'
        ]);
    }

    #[Route('/equipment/{id}/delete', name: 'aparatura_pomiarowa_equipment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_equipment_' . $equipment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $equipmentName = $equipment->getName();
            $this->aparaturaPomiarowaService->deleteEquipment($equipment, $user);
            
            $this->addFlash('success', sprintf('Sprzęt "%s" został usunięty pomyślnie.', $equipmentName));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania sprzętu.');
            $this->logger->error('Failed to delete aparaturaPomiarowa equipment', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_index');
    }

    #[Route('/equipment/{id}/assign', name: 'aparatura_pomiarowa_equipment_assign', requirements: ['id' => '\d+'])]
    public function assignEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        $form = $this->createForm(EquipmentAssignType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $assignee = $form->get('assignee')->getData();
                $notes = $form->get('notes')->getData();
                
                $this->aparaturaPomiarowaService->assignEquipment($equipment, $assignee, $user, $notes);
                
                $this->addFlash('success', sprintf('Sprzęt został przypisany do użytkownika %s.', $assignee->getFullName()));
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
                
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
                $this->logger->error('Failed to assign aparaturaPomiarowa equipment', [
                    'equipment_id' => $equipment->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/equipment/assign.html.twig', [
            'form' => $form,
            'equipment' => $equipment
        ]);
    }

    #[Route('/equipment/{id}/unassign', name: 'aparatura_pomiarowa_equipment_unassign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unassignEquipment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('unassign_equipment_' . $equipment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $previousAssignee = $equipment->getAssignedTo();
            $this->aparaturaPomiarowaService->unassignEquipment($equipment, $user);
            
            $this->addFlash('success', sprintf('Cofnięto przypisanie sprzętu od użytkownika %s.', $previousAssignee?->getFullName()));
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd.');
            $this->logger->error('Failed to unassign aparaturaPomiarowa equipment', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/search', name: 'aparatura_pomiarowa_search')]
    public function search(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }
        
        try {
            $equipment = $this->aparaturaPomiarowaService->searchEquipment($query, 10);
            $equipmentSets = $this->aparaturaPomiarowaService->searchEquipmentSets($query, 10);
            
            $results = [];
            
            foreach ($equipment as $item) {
                $results[] = [
                    'type' => 'equipment',
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'inventory_number' => $item->getInventoryNumber(),
                    'status' => $item->getStatusDisplayName(),
                    'url' => $this->generateUrl('aparatura_pomiarowa_equipment_show', ['id' => $item->getId()])
                ];
            }
            
            foreach ($equipmentSets as $item) {
                $results[] = [
                    'type' => 'equipment_set',
                    'id' => $item->getId(),
                    'name' => $item->getName(),
                    'equipment_count' => $item->getEquipmentCount(),
                    'status' => $item->getStatusDisplayName(),
                    'url' => $this->generateUrl('aparatura_pomiarowa_equipment_set_show', ['id' => $item->getId()])
                ];
            }
            
            // Audit search
            $this->auditService->logUserAction($user, 'aparatura_pomiarowa_search', [
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

    #[Route('/my-equipment', name: 'aparatura_pomiarowa_my_equipment')]
    public function myEquipment(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja - każdy użytkownik może zobaczyć swój przypisany sprzęt
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $assignedEquipment = $this->aparaturaPomiarowaService->getUserAssignedEquipment($user);
        
        // Audit
        $this->auditService->logUserAction($user, 'view_my_aparatura_pomiarowa_equipment', [
            'equipment_count' => count($assignedEquipment['equipment']),
            'equipment_sets_count' => count($assignedEquipment['equipment_sets'])
        ], $request);
        
        return $this->render('aparatura-pomiarowa/my-equipment.html.twig', [
            'equipment' => $assignedEquipment['equipment'],
            'equipment_sets' => $assignedEquipment['equipment_sets']
        ]);
    }

    // === ATTACHMENT MANAGEMENT ===

    #[Route('/equipment/{id}/attachment/upload', name: 'aparatura_pomiarowa_equipment_attachment_upload', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadEquipmentAttachment(AsekuracyjnyEquipment $equipment, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('upload_equipment_attachment_' . $equipment->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Ensure entity is managed by entity manager
            $equipment = $this->entityManager->find(AsekuracyjnyEquipment::class, $equipment->getId());
            if (!$equipment) {
                throw new \RuntimeException('Equipment not found');
            }
            
            $uploadedFiles = $request->files->get('attachments', []);
            $description = $request->request->get('description', '');
            
            if (empty($uploadedFiles)) {
                $this->addFlash('error', 'Nie wybrano żadnych plików do przesłania.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
            }
            
            $uploadedCount = 0;
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/equipment/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($uploadedFiles as $uploadedFile) {
                if ($uploadedFile instanceof UploadedFile && $uploadedFile->isValid()) {
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
                        continue;
                    }
                    
                    // Generate unique filename and get file info before moving
                    $filename = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
                    $originalName = $uploadedFile->getClientOriginalName();
                    $fileSize = $uploadedFile->getSize();
                    $mimeType = $uploadedFile->getMimeType();
                    
                    $uploadedFile->move($uploadDir, $filename);
                    
                    // Add to equipment attachments
                    $equipment->addAttachment([
                        'filename' => $filename,
                        'original_name' => $originalName,
                        'size' => $fileSize,
                        'mime_type' => $mimeType,
                        'uploaded_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                        'uploaded_by' => $user->getFullName(),
                        'description' => $description
                    ]);
                    
                    $uploadedCount++;
                }
            }
            
            if ($uploadedCount > 0) {
                // Set updated by and updated at
                $equipment->setUpdatedBy($user);
                $equipment->setUpdatedAt(new \DateTime());
                
                $this->entityManager->persist($equipment);
                $this->entityManager->flush();
                
                $this->addFlash('success', sprintf('Przesłano %d załączników pomyślnie.', $uploadedCount));
                
                // Audit
                $this->auditService->logUserAction($user, 'upload_equipment_attachment', [
                    'equipment_id' => $equipment->getId(),
                    'files_count' => $uploadedCount,
                    'description' => $description
                ], $request);
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas przesyłania załączników.');
            $this->logger->error('Failed to upload equipment attachment', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
    }

    #[Route('/equipment/{id}/attachment/{filename}/download', name: 'aparatura_pomiarowa_equipment_attachment_download', requirements: ['id' => '\d+'])]
    public function downloadEquipmentAttachment(AsekuracyjnyEquipment $equipment, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Check if user can view this equipment
        if (!$this->canUserViewEquipment($user, $equipment)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego sprzętu.');
        }
        
        // Find attachment
        $attachment = null;
        foreach ($equipment->getAttachments() as $att) {
            if ($att['filename'] === $filename) {
                $attachment = $att;
                break;
            }
        }
        
        if (!$attachment) {
            throw $this->createNotFoundException('Załącznik nie został znaleziony.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/equipment/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik nie został znaleziony na serwerze.');
        }
        
        // Audit
        $this->auditService->logUserAction($user, 'download_equipment_attachment', [
            'equipment_id' => $equipment->getId(),
            'filename' => $filename,
            'original_name' => $attachment['original_name']
        ], $request);
        
        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $attachment['original_name']);
        
        return $response;
    }

    #[Route('/equipment/{id}/attachment/{filename}/delete', name: 'aparatura_pomiarowa_equipment_attachment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEquipmentAttachment(AsekuracyjnyEquipment $equipment, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_attachment_' . $equipment->getId() . '_' . $filename, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Find and remove attachment from database
            $attachment = null;
            foreach ($equipment->getAttachments() as $att) {
                if ($att['filename'] === $filename) {
                    $attachment = $att;
                    break;
                }
            }
            
            if (!$attachment) {
                $this->addFlash('error', 'Załącznik nie został znaleziony.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
            }
            
            $equipment->removeAttachment($filename);
            $this->entityManager->flush();
            
            // Remove file from filesystem
            $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/equipment/' . $filename;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $this->addFlash('success', sprintf('Załącznik "%s" został usunięty.', $attachment['original_name']));
            
            // Audit
            $this->auditService->logUserAction($user, 'delete_equipment_attachment', [
                'equipment_id' => $equipment->getId(),
                'filename' => $filename,
                'original_name' => $attachment['original_name']
            ], $request);
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania załącznika.');
            $this->logger->error('Failed to delete equipment attachment', [
                'equipment_id' => $equipment->getId(),
                'filename' => $filename,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()]);
    }

    // === PRIVATE HELPER METHODS ===

    private function canUserViewEquipment(User $user, AsekuracyjnyEquipment $equipment): bool
    {
        // Admini i edytorzy mogą widzieć wszystko
        if ($this->authorizationService->checkAnyPermission($user, 'aparatura_pomiarowa', ['EDIT', 'DELETE', 'ASSIGN'])) {
            return true;
        }
        
        // Użytkownicy z uprawnieniem VIEW mogą widzieć wszystkie
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW')) {
            return true;
        }
        
        // Użytkownik może widzieć swój przypisany sprzęt
        if ($equipment->getAssignedTo() === $user) {
            return true;
        }
        
        // Użytkownicy z uprawnieniem VIEW_OWN mogą widzieć tylko swój przypisany sprzęt
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW_OWN')) {
            return $equipment->getAssignedTo() === $user;
        }
        
        return false;
    }
}