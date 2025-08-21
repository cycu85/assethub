<?php

namespace App\AsekuracyjnySPM\Controller;

use App\AsekuracyjnySPM\Entity\AsekuracyjnyReview;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipment;
use App\AsekuracyjnySPM\Entity\AsekuracyjnyEquipmentSet;
use App\AsekuracyjnySPM\Service\AsekuracyjnyService;
use App\AsekuracyjnySPM\Service\ReviewService;
use App\AsekuracyjnySPM\Form\AsekuracyjnyReviewType;
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

#[Route('/asekuracja/reviews')]
class ReviewController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AsekuracyjnyService $asekuracyjnyService,
        private ReviewService $reviewService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/', name: 'asekuracja_review_index')]
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
            'review_type' => $request->query->get('review_type'),
            'result' => $request->query->get('result'),
            'equipment_id' => $request->query->get('equipment_id'),
            'equipment_set_id' => $request->query->get('equipment_set_id'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $reviewsPagination = $this->asekuracyjnyService->getReviewsWithPagination($page, 25, $filters);
        $statistics = $this->asekuracyjnyService->getReviewStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_reviews_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_reviews' => $reviewsPagination['total']
        ], $request);
        
        return $this->render('asekuracja/review/index.html.twig', [
            'reviews' => $reviewsPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/new', name: 'asekuracja_review_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $review = new AsekuracyjnyReview();
        $form = $this->createForm(AsekuracyjnyReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sprawdzenie czy wybrano sprzęt lub zestaw
                if (!$review->getEquipment() && !$review->getEquipmentSet()) {
                    $this->addFlash('error', 'Musisz wybrać sprzęt lub zestaw sprzętu do przeglądu.');
                    return $this->render('asekuracja/review/form.html.twig', [
                        'review' => $review,
                        'form' => $form,
                        'page_title' => 'Nowy przegląd',
                        'can_edit' => true,
                        'can_delete' => false
                    ]);
                }

                // Sprawdzenie czy nie wybrano obu naraz
                if ($review->getEquipment() && $review->getEquipmentSet()) {
                    $this->addFlash('error', 'Możesz wybrać albo sprzęt, albo zestaw sprzętu, ale nie oba naraz.');
                    return $this->render('asekuracja/review/form.html.twig', [
                        'review' => $review,
                        'form' => $form,
                        'page_title' => 'Nowy przegląd',
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

                $this->addFlash('success', 'Przegląd został utworzony pomyślnie.');
                return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating review', [
                    'error' => $e->getMessage(),
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia przeglądu.');
            }
        }
        
        return $this->render('asekuracja/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'page_title' => 'Nowy przegląd',
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/{id}', name: 'asekuracja_review_show', requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie uprawnień
        $canEdit = $this->authorizationService->hasPermission($user, 'asekuracja', 'REVIEW');
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_asekuracja_review', [
            'review_id' => $review->getId(),
            'review_number' => $review->getReviewNumber()
        ], $request);
        
        return $this->render('asekuracja/review/show.html.twig', [
            'review' => $review,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/{id}/edit', name: 'asekuracja_review_edit', requirements: ['id' => '\d+'])]
    public function edit(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // Sprawdzenie uprawnień
        $canDelete = $this->authorizationService->hasPermission($user, 'asekuracja', 'DELETE');
        
        $form = $this->createForm(AsekuracyjnyReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $review->setUpdatedBy($user);
                $this->entityManager->flush();

                // Audit
                $this->auditService->logUserAction($user, 'update_asekuracja_review', [
                    'review_id' => $review->getId(),
                    'review_number' => $review->getReviewNumber()
                ], $request);

                $this->addFlash('success', 'Przegląd został zaktualizowany pomyślnie.');
                return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error updating review', [
                    'error' => $e->getMessage(),
                    'review_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas aktualizowania przeglądu.');
            }
        }
        
        return $this->render('asekuracja/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'page_title' => 'Edycja przeglądu',
            'can_edit' => true,
            'can_delete' => $canDelete
        ]);
    }

    #[Route('/new/equipment/{id}', name: 'asekuracja_review_new_for_equipment', requirements: ['id' => '\d+'])]
    public function newForEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $equipment = $this->asekuracyjnyService->getEquipment($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Sprzęt nie został znaleziony');
        }
        
        $review = new AsekuracyjnyReview();
        $review->setEquipment($equipment);
        $review->setPlannedDate(new \DateTime('+7 days')); // Domyślnie za tydzień
        
        $form = $this->createForm(AsekuracyjnyReviewType::class, $review, [
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

                $this->addFlash('success', 'Przegląd dla sprzętu został utworzony pomyślnie.');
                return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating equipment review', [
                    'error' => $e->getMessage(),
                    'equipment_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia przeglądu.');
            }
        }
        
        return $this->render('asekuracja/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'equipment' => $equipment,
            'page_title' => 'Nowy przegląd - ' . $equipment->getName(),
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/new/equipment-set/{id}', name: 'asekuracja_review_new_for_set', requirements: ['id' => '\d+'])]
    public function newForEquipmentSet(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $equipmentSet = $this->asekuracyjnyService->getEquipmentSet($id);
        if (!$equipmentSet) {
            throw $this->createNotFoundException('Zestaw nie został znaleziony');
        }
        
        $review = new AsekuracyjnyReview();
        $review->setEquipmentSet($equipmentSet);
        $review->setPlannedDate(new \DateTime('+7 days')); // Domyślnie za tydzień
        
        $form = $this->createForm(AsekuracyjnyReviewType::class, $review, [
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

                $this->addFlash('success', 'Przegląd dla zestawu został utworzony pomyślnie.');
                return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
                
            } catch (\Exception $e) {
                $this->logger->error('Error creating equipment set review', [
                    'error' => $e->getMessage(),
                    'equipment_set_id' => $id,
                    'user_id' => $user->getId()
                ]);
                $this->addFlash('error', 'Wystąpił błąd podczas tworzenia przeglądu.');
            }
        }
        
        return $this->render('asekuracja/review/form.html.twig', [
            'review' => $review,
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'page_title' => 'Nowy przegląd - ' . $equipmentSet->getName(),
            'can_edit' => true,
            'can_delete' => false
        ]);
    }

    #[Route('/{id}/send', name: 'asekuracja_review_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sendReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('send_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $this->asekuracyjnyService->sendReview($review, $user);
            $this->addFlash('success', 'Przegląd został wysłany pomyślnie.');
            
        } catch (\Exception $e) {
            $this->logger->error('Error sending review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas wysyłania przeglądu.');
        }
        
        return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/delete', name: 'asekuracja_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'DELETE', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
        if (!$review) {
            throw $this->createNotFoundException('Przegląd nie został znaleziony');
        }
        
        // CSRF protection
        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        
        try {
            $reviewNumber = $review->getReviewNumber();
            $this->asekuracyjnyService->deleteReview($review, $user);
            $this->addFlash('success', sprintf('Przegląd "%s" został usunięty pomyślnie.', $reviewNumber));
            
        } catch (\Exception $e) {
            $this->logger->error('Error deleting review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas usuwania przeglądu.');
        }
        
        return $this->redirectToRoute('asekuracja_review_index');
    }

    #[Route('/{id}/complete', name: 'asekuracja_review_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function completeReview(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'asekuracja', 'REVIEW', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
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
                return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
            }

            // Obsługa załączników
            $attachments = $request->files->get('attachments', []);
            if (!empty($attachments)) {
                $uploadedFiles = $this->handleFileUploads($attachments, $review, $user);
                $completionData['attachments'] = $uploadedFiles;
            }

            // Zakończenie przeglądu
            $this->asekuracyjnyService->completeReview($review, $completionData, $user);
            
            $this->addFlash('success', sprintf('Przegląd "%s" został zakończony pomyślnie.', $review->getReviewNumber()));
            
        } catch (\Exception $e) {
            $this->logger->error('Error completing review', [
                'error' => $e->getMessage(),
                'review_id' => $id,
                'user_id' => $user->getId()
            ]);
            $this->addFlash('error', 'Wystąpił błąd podczas zakończenia przeglądu: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('asekuracja_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/attachment/{filename}', name: 'asekuracja_review_attachment_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadAttachment(int $id, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'asekuracja', $request);
        
        $review = $this->asekuracyjnyService->getReview($id);
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

    private function handleFileUploads(array $files, AsekuracyjnyReview $review, User $user): array
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

}
