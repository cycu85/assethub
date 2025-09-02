<?php

namespace App\AparaturaPomiarowa\Controller;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaReviewService;
use App\AparaturaPomiarowa\Form\AparaturaPomiarowaReviewType;
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
use Doctrine\ORM\EntityManagerInterface;

#[Route('/aparatura-pomiarowa/reviews')]
class AparaturaPomiarowaReviewController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AparaturaPomiarowaService $aparaturaPomiarowaService,
        private AparaturaPomiarowaReviewService $reviewService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'aparatura_pomiarowa_review_index')]
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
            'review_type' => $request->query->get('review_type'),
            'result' => $request->query->get('result'),
            'equipment_id' => $request->query->get('equipment_id'),
            'equipment_set_id' => $request->query->get('equipment_set_id'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $reviewsPagination = $this->aparaturaPomiarowaService->getReviewsWithPagination($page, 25, $filters);
        $statistics = $this->aparaturaPomiarowaService->getReviewStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_reviews_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_reviews' => $reviewsPagination['total']
        ], $request);
        
        return $this->render('aparatura-pomiarowa/review/index.html.twig', [
            'reviews' => $reviewsPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/new', name: 'aparatura_pomiarowa_review_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = new AparaturaPomiarowaReview();
        // Ustawienie domyślnej daty planowanego przeglądu na dziś
        $review->setPlannedDate(new \DateTime());
        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sprawdzenie czy wybrano sprzęt lub zestaw
                if (!$review->getEquipment() && !$review->getEquipmentSet()) {
                    $this->addFlash('error', 'Musisz wybrać sprzęt lub zestaw sprzętu do przeglądu.');
                    return $this->render('aparatura-pomiarowa/review/form.html.twig', [
                        'review' => $review,
                        'form' => $form,
                        'page_title' => 'Nowa kalibracja',
                        'can_edit' => true,
                        'can_delete' => false
                    ]);
                }

                // Sprawdzenie czy nie wybrano obu naraz
                if ($review->getEquipment() && $review->getEquipmentSet()) {
                    $this->addFlash('error', 'Możesz wybrać albo sprzęt, albo zestaw sprzętu, ale nie oba naraz.');
                    return $this->render('aparatura-pomiarowa/review/form.html.twig', [
                        'review' => $review,
                        'form' => $form,
                        'page_title' => 'Nowa kalibracja',
                        'can_edit' => true,
                        'can_delete' => false
                    ]);
                }

                // Przygotowanie danych dla ReviewService
                $data = [
                    'planned_date' => $review->getPlannedDate(),
                    'review_type' => $review->getReviewType(),
                    'review_company' => $review->getReviewCompany(),
                    'notes' => $review->getNotes()
                ];

                // Utworzenie przeglądu przez ReviewService
                if ($review->getEquipment()) {
                    $review = $this->reviewService->createEquipmentReview($review->getEquipment(), $data, $user);
                } elseif ($review->getEquipmentSet()) {
                    $review = $this->reviewService->createEquipmentSetReview($review->getEquipmentSet(), $data, $user);
                }

                // Audit
                $this->auditService->logUserAction($user, 'create_asekuracja_review', [
                    'review_id' => $review->getId(),
                    'review_number' => $review->getReviewNumber(),
                    'equipment_id' => $review->getEquipment()?->getId(),
                    'equipment_set_id' => $review->getEquipmentSet()?->getId(),
                    'review_type' => $review->getReviewType(),
                    'planned_date' => $review->getPlannedDate()?->format('Y-m-d')
                ], $request);

                $this->addFlash('success', 'Kalibracja została utworzona pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating review', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia kalibracji.');
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'page_title' => 'Nowa kalibracja',
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/{id}', name: 'aparatura_pomiarowa_review_show', requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie uprawnień
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'REVIEW');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber()
        ], $request);
        
        return $this->render('aparatura-pomiarowa/review/show.html.twig', [
            'review' => $review,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/{id}/edit', name: 'aparatura_pomiarowa_review_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie uprawnień
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');
        
        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $review->setUpdatedBy($user);
                $this->entityManager->flush();

                // Audit
                $this->auditService->logUserAction($user, 'update_aparatura_pomiarowa_review', [
                    'review_id' => $review->getId(),
                    'review_number' => $review->getReviewNumber()
                ], $request);

                $this->addFlash('success', 'Kalibracja została zaktualizowana pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error updating review', [
                    'error' => $e->getMessage(),
                    'review_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas aktualizowania kalibracji.');
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'page_title' => 'Edycja kalibracji',
            'can_edit' => true,
            'can_delete' => $canDelete
        ]);
    }

    #[Route('/new/equipment/{id}', name: 'aparatura_pomiarowa_review_new_for_equipment', requirements: ['id' => '\d+'])]
    public function newForEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $equipment = $this->aparaturaPomiarowaService->getEquipment($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Sprzęt nie został znaleziony');
        }
        
        $review = new AparaturaPomiarowaReview();
        $review->setEquipment($equipment);
        $review->setPlannedDate(new \DateTime('+7 days')); // Domyślnie za tydzień
        
        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review, [
            'equipment' => $equipment
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Przygotowanie danych dla ReviewService
                $data = [
                    'planned_date' => $review->getPlannedDate(),
                    'review_type' => $review->getReviewType(),
                    'review_company' => $review->getReviewCompany(),
                    'notes' => $review->getNotes()
                ];

                // Utworzenie przeglądu przez ReviewService
                $review = $this->reviewService->createEquipmentReview($equipment, $data, $user);

                $this->addFlash('success', 'Kalibracja dla mierników został utworzony pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating equipment review', [
                    'error' => $e->getMessage(),
                    'equipment_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia kalibracji.');
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'equipment' => $equipment,
            'page_title' => 'Nowa kalibracja - ' . $equipment->getName(),
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/new/equipment-set/{id}', name: 'aparatura_pomiarowa_review_new_for_set', requirements: ['id' => '\d+'])]
    public function newForEquipmentSet(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $equipmentSet = $this->aparaturaPomiarowaService->getEquipmentSet($id);
        if (!$equipmentSet) {
            throw $this->createNotFoundException('Zestaw nie został znaleziony');
        }
        
        $review = new AparaturaPomiarowaReview();
        $review->setEquipmentSet($equipmentSet);
        $review->setPlannedDate(new \DateTime('+7 days')); // Domyślnie za tydzień
        
        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review, [
            'equipment_set' => $equipmentSet
        ]);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Przygotowanie danych dla ReviewService
                $data = [
                    'planned_date' => $review->getPlannedDate(),
                    'review_type' => $review->getReviewType(),
                    'review_company' => $review->getReviewCompany(),
                    'notes' => $review->getNotes()
                ];

                // Utworzenie przeglądu przez ReviewService
                $review = $this->reviewService->createEquipmentSetReview($equipmentSet, $data, $user);

                $this->addFlash('success', 'Kalibracja dla zestawu został utworzony pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating equipment set review', [
                    'error' => $e->getMessage(),
                    'equipment_set_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia kalibracji.');
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'page_title' => 'Nowa kalibracja - ' . $equipmentSet->getName(),
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/{id}/send', name: 'aparatura_pomiarowa_review_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sendReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('send_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $this->aparaturaPomiarowaService->sendReview($review, $user);
            $this->addFlash('success', 'Kalibracja została wysłana pomyślnie.');
            
        } catch (\Exception $e) {
            $this->logger->error('Error sending review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas wysyłania kalibracji.');
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/delete', name: 'aparatura_pomiarowa_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $reviewNumber = $review->getReviewNumber();
            $this->aparaturaPomiarowaService->deleteReview($review, $user);
            $this->addFlash('success', sprintf('Przegląd "%s" został usunięty pomyślnie.', $reviewNumber));
            
        } catch (\Exception $e) {
            $this->logger->error('Error deleting review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania kalibracji.');
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_review_index');
    }

    #[Route('/{id}/complete', name: 'aparatura_pomiarowa_review_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('complete_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            // Przygotowanie danych z formularza
            $completionData = [
                'completed_date' => $request->request->get('completed_date') ? 
                    new \DateTime($request->request->get('completed_date')) : new \DateTime(),
                'result' => $request->request->get('result'),
                'certificate_number' => $request->request->get('certificate_number'),
                'cost' => $request->request->get('cost'),
                'findings' => $request->request->get('findings'),
                'recommendations' => $request->request->get('recommendations'),
                'next_review_date' => $request->request->get('next_review_date') ? 
                    new \DateTime($request->request->get('next_review_date')) : null
            ];

            // Walidacja wymaganego pola 'result'
            if (empty($completionData['result'])) {
                $this->addFlash('error', 'Wynik przeglądu jest wymagany.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
            }

            // Obsługa załączników
            $attachments = $request->files->get('attachments', []);
            if (!empty($attachments)) {
                $uploadedFiles = $this->handleFileUploads($attachments, $review, $user);
                $completionData['attachments'] = $uploadedFiles;
            }

            // Zakończenie przeglądu
            $this->aparaturaPomiarowaService->completeReview($review, $completionData, $user);
            
            $this->addFlash('success', sprintf('Przegląd "%s" został zakończony pomyślnie.', $review->getReviewNumber()));
            
        } catch (\Exception $e) {
            $this->logger->error('Error completing review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas zakończenia kalibracji: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/attachment/{filename}', name: 'aparatura_pomiarowa_review_attachment_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadAttachment(int $id, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie czy załącznik istnieje w przglądzie
        $attachments = $review->getAttachments();
        $attachment = null;
        
        foreach ($attachments as $att) {
            if ($att['filename'] === $filename) {
                $attachment = $att;
                break;
            }
        }
        
        if (!$attachment) {
            throw $this->createNotFoundException('Załącznik nie został znaleziony');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/reviews/' . $review->getId() . '/' . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik nie został znaleziony na serwerze');
        }
        
        // Audit
        $this->auditService->logUserAction($user, 'download_asekuracja_review_attachment', [
            'review_id' => $review->getId(),
            'filename' => $filename,
            'original_name' => $attachment['original_name']
        ], $request);
        
        return $this->file($filePath, $attachment['original_name']);
    }

    private function handleFileUploads(array $files, AparaturaPomiarowaReview $review, User $user): array
    {
        $uploadedFiles = [];
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/reviews/' . $review->getId();
        
        // Tworzenie katalogu jeśli nie istnieje
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                // Sprawdzenie rozmiaru pliku (max 10MB)
                if ($file->getSize() > 10 * 1024 * 1024) {
                    throw new \Exception('Plik "' . $file->getClientOriginalName() . '" jest za duży (max 10MB).');
                }
                
                // Sprawdzenie typu pliku
                $allowedMimeTypes = [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ];
                
                if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                    throw new \Exception('Nieprawidłowy format pliku: ' . $file->getClientOriginalName());
                }
                
                // Generowanie unikalnej nazwy pliku
                $originalName = $file->getClientOriginalName();
                $extension = $file->guessExtension();
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                
                // Przeniesienie pliku
                $file->move($uploadDir, $fileName);
                
                $uploadedFiles[] = [
                    'filename' => $fileName,
                    'original_name' => $originalName,
                    'uploaded_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'uploaded_by' => $user->getId(),
                    'size' => filesize($uploadDir . '/' . $fileName)
                ];
            }
        }
        
        return $uploadedFiles;
    }

    #[Route('/{id}/equipment/add', name: 'aparatura_pomiarowa_review_add_equipment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie czy przegląd nie jest zakończony
        if ($review->getStatus() === 'completed') {
            $this->addFlash('error', 'Nie można modyfikować zakończonej kalibracji.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('add_review_equipment_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        $equipmentIds = $request->request->all('equipment_ids');
        
        if (empty($equipmentIds)) {
            $this->addFlash('error', 'Nie wybrano żadnego mierników do dodania.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
        }
        
        $addedCount = 0;
        $errors = [];
        
        try {
            $this->entityManager->beginTransaction();
            
            foreach ($equipmentIds as $equipmentId) {
                try {
                    $equipment = $this->aparaturaPomiarowaService->getEquipmentRepository()->find($equipmentId);
                    if (!$equipment) {
                        $errors[] = "Sprzęt o ID {$equipmentId} nie został znaleziony.";
                        continue;
                    }
                    
                    $this->reviewService->addEquipmentToReview($review, $equipment, $user);
                    $addedCount++;
                    
                } catch (BusinessLogicException $e) {
                    $errors[] = sprintf('Błąd przy dodawaniu "%s": %s', $equipment->getName() ?? "ID {$equipmentId}", $e->getMessage());
                } catch (\Exception $e) {
                    $errors[] = sprintf('Nieoczekiwany błąd przy dodawaniu sprzętu ID %s', $equipmentId);
                    $this->logger->error('Failed to add equipment to review', [
                        'review_id' => $review->getId(),
                        'equipment_id' => $equipmentId,
                        'error' => $e->getMessage(),
                        'user' => $user->getUsername()
                    ]);
                }
            }
            
            $this->entityManager->commit();
            
            // Flash messages
            if ($addedCount > 0) {
                $message = sprintf('Dodano %d %s do przeglądu.', 
                    $addedCount, 
                    $addedCount === 1 ? 'element' : ($addedCount < 5 ? 'elementy' : 'elementów')
                );
                $this->addFlash('success', $message);
            }
            
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->addFlash('error', 'Wystąpił błąd podczas dodawania sprzętu. Operacja została wycofana.');
            $this->logger->error('Bulk equipment addition to review failed', [
                'review_id' => $review->getId(),
                'equipment_ids' => $equipmentIds,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
    }

    #[Route('/{id}/equipment/{equipmentId}/remove', name: 'aparatura_pomiarowa_review_remove_equipment', requirements: ['id' => '\d+', 'equipmentId' => '\d+'], methods: ['POST'])]
    public function removeEquipment(int $id, int $equipmentId, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $review = $this->aparaturaPomiarowaService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie czy przegląd nie jest zakończony
        if ($review->getStatus() === 'completed') {
            $this->addFlash('error', 'Nie można modyfikować zakończonej kalibracji.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('remove_review_equipment_' . $review->getId() . '_' . $equipmentId, $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        // Znajdź AparaturaPomiarowaReviewEquipment
        $reviewEquipment = null;
        foreach ($review->getReviewEquipments() as $re) {
            if ($re->getId() === $equipmentId) {
                $reviewEquipment = $re;
                break;
            }
        }
        
        if (!$reviewEquipment) {
            $this->addFlash('error', 'Element nie został znaleziony w przeglądzie.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
        }
        
        try {
            $equipmentName = $reviewEquipment->getEquipmentDisplayName();
            $this->reviewService->removeEquipmentFromReview($review, $reviewEquipment, $user);
            
            $this->addFlash('success', sprintf('Usunięto "%s" z przeglądu.', $equipmentName));
            
            // Audit
            $this->auditService->logUserAction($user, 'remove_equipment_from_review', [
                'review_id' => $review->getId(),
                'review_equipment_id' => $equipmentId,
                'equipment_name' => $equipmentName
            ], $request);
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania sprzętu z przeglądu.');
            $this->logger->error('Failed to remove equipment from review', [
                'review_id' => $review->getId(),
                'review_equipment_id' => $equipmentId,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }
        
        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $id]);
    }

}
