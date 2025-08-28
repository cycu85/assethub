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
        private AparaturaPomiarowaService $aparaturaService,
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
            'needs_review' => $request->query->getBoolean('needs_calibration'),
            'overdue_review' => $request->query->getBoolean('overdue_calibration'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $equipmentPagination = $this->aparaturaService->getEquipmentWithPagination($page, 0, $filters);
        $statistics = $this->aparaturaService->getEquipmentStatistics();
        $allEquipmentSets = $this->aparaturaService->getAllEquipmentSets();
        
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
        
        return $this->render('aparatura_pomiarowa/equipment/index.html.twig', [
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
        
        $equipment = new AparaturaPomiarowaEquipment();
        $form = $this->createForm(AparaturaPomiarowaEquipmentType::class, $equipment, [
            'include_submit' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $uploadedFiles = [];
                foreach ($request->files->get('attachments', []) as $file) {
                    if ($file instanceof UploadedFile) {
                        $uploadedFiles[] = $file;
                    }
                }

                $this->aparaturaService->createEquipment($equipment, $user, $uploadedFiles);

                $this->addFlash('success', 'Urządzenie zostało utworzone pomyślnie.');
                
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', [
                    'id' => $equipment->getId()
                ]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
                foreach ($e->getViolations() as $violation) {
                    $this->addFlash('error', $violation->getPropertyPath() . ': ' . $violation->getMessage());
                }
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas tworzenia urządzenia.');
                $this->logger->error('Equipment creation error', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }

        return $this->render('aparatura_pomiarowa/equipment/new.html.twig', [
            'form' => $form->createView(),
            'equipment' => $equipment,
        ]);
    }

    #[Route('/equipment/{id}', name: 'aparatura_pomiarowa_equipment_show', requirements: ['id' => '\d+'])]
    public function showEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'EDIT');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        $canAssign = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'ASSIGN');
        $canReview = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canTransfer = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');

        // Pobranie powiązanych danych
        $reviews = $this->aparaturaService->getReviewsForEquipment($equipment);
        $transfers = $this->aparaturaService->getTransfersForEquipment($equipment);
        $activeTransfer = $this->aparaturaService->getActiveTransferForEquipment($equipment);

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_equipment', [
            'equipment_id' => $equipment->getId(),
            'inventory_number' => $equipment->getInventoryNumber()
        ], $request);
        
        return $this->render('aparatura_pomiarowa/equipment/show.html.twig', [
            'equipment' => $equipment,
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

    #[Route('/equipment/{id}/edit', name: 'aparatura_pomiarowa_equipment_edit', requirements: ['id' => '\d+'])]
    public function editEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'EDIT', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        $form = $this->createForm(AparaturaPomiarowaEquipmentType::class, $equipment, [
            'include_submit' => false
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $uploadedFiles = [];
                foreach ($request->files->get('attachments', []) as $file) {
                    if ($file instanceof UploadedFile) {
                        $uploadedFiles[] = $file;
                    }
                }

                $this->aparaturaService->updateEquipment($equipment, $user, $uploadedFiles);

                $this->addFlash('success', 'Urządzenie zostało zaktualizowane pomyślnie.');
                
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', [
                    'id' => $equipment->getId()
                ]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
                foreach ($e->getViolations() as $violation) {
                    $this->addFlash('error', $violation->getPropertyPath() . ': ' . $violation->getMessage());
                }
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas aktualizacji urządzenia.');
                $this->logger->error('Equipment update error', [
                    'equipment_id' => $equipment->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }

        return $this->render('aparatura_pomiarowa/equipment/edit.html.twig', [
            'form' => $form->createView(),
            'equipment' => $equipment,
        ]);
    }

    #[Route('/equipment/{id}/delete', name: 'aparatura_pomiarowa_equipment_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('delete_equipment_' . $equipment->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
        }

        try {
            $this->aparaturaService->deleteEquipment($equipment, $user);
            $this->addFlash('success', 'Urządzenie zostało usunięte pomyślnie.');
            
            return $this->redirectToRoute('aparatura_pomiarowa_index');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania urządzenia.');
            $this->logger->error('Equipment deletion error', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
    }

    #[Route('/equipment/{id}/assign', name: 'aparatura_pomiarowa_equipment_assign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function assignEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('assign_equipment_' . $equipment->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
        }

        $assignToUserId = $request->get('assign_to_user_id');
        if (!$assignToUserId) {
            $this->addFlash('error', 'Nie wybrano użytkownika do przypisania.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
        }

        try {
            $assignToUser = $this->entityManager->getRepository(User::class)->find($assignToUserId);
            if (!$assignToUser) {
                $this->addFlash('error', 'Wybrany użytkownik nie istnieje.');
                return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
            }

            $this->aparaturaService->assignEquipmentToUser($equipment, $assignToUser, $user);
            $this->addFlash('success', 'Urządzenie zostało przypisane do użytkownika ' . $assignToUser->getFullName());
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przypisywania urządzenia.');
            $this->logger->error('Equipment assignment error', [
                'equipment_id' => $equipment->getId(),
                'assign_to' => $assignToUserId,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
    }

    #[Route('/equipment/{id}/unassign', name: 'aparatura_pomiarowa_equipment_unassign', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unassignEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'ASSIGN', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('unassign_equipment_' . $equipment->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
        }

        try {
            $this->aparaturaService->unassignEquipmentFromUser($equipment, $user);
            $this->addFlash('success', 'Urządzenie zostało odłączone od użytkownika.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas odłączania urządzenia.');
            $this->logger->error('Equipment unassignment error', [
                'equipment_id' => $equipment->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_equipment_show', ['id' => $id]);
    }

    #[Route('/equipment/{id}/attachment/{filename}', name: 'aparatura_pomiarowa_equipment_download_attachment')]
    public function downloadEquipmentAttachment(int $id, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $equipment = $this->aparaturaService->getEquipmentById($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Urządzenie nie zostało znalezione.');
        }

        // Sprawdź czy załącznik należy do tego urządzenia
        if (!in_array($filename, $equipment->getAttachments())) {
            throw $this->createNotFoundException('Załącznik nie został znaleziony.');
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura_pomiarowa/equipment/';
        $filePath = $uploadDir . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik nie został znaleziony na dysku.');
        }

        // Audit
        $this->auditService->logUserAction($user, 'download_aparatura_pomiarowa_equipment_attachment', [
            'equipment_id' => $equipment->getId(),
            'filename' => $filename
        ], $request);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        
        return $response;
    }

    #[Route('/search', name: 'aparatura_pomiarowa_search', methods: ['GET'])]
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
            $results = $this->aparaturaService->searchEquipment($query, $limit);
            
            $response = array_map(function($equipment) {
                return [
                    'id' => $equipment->getId(),
                    'name' => $equipment->getName(),
                    'inventory_number' => $equipment->getInventoryNumber(),
                    'status' => $equipment->getStatusDisplayName(),
                    'type' => $equipment->getEquipmentType(),
                    'url' => $this->generateUrl('aparatura_pomiarowa_equipment_show', ['id' => $equipment->getId()])
                ];
            }, $results);
            
            return new JsonResponse($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Equipment search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd podczas wyszukiwania'], 500);
        }
    }

    #[Route('/statistics', name: 'aparatura_pomiarowa_statistics')]
    public function statistics(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $statistics = $this->aparaturaService->getEquipmentStatistics();
        $reviewStatistics = $this->aparaturaService->getReviewStatistics();
        $transferStatistics = $this->aparaturaService->getTransferStatistics();
        
        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_statistics', [], $request);
        
        return $this->render('aparatura_pomiarowa/statistics.html.twig', [
            'equipment_statistics' => $statistics,
            'review_statistics' => $reviewStatistics,
            'transfer_statistics' => $transferStatistics,
        ]);
    }
}