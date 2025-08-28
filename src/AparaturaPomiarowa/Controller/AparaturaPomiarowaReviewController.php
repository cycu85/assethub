<?php

namespace App\AparaturaPomiarowa\Controller;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaReview;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaReviewService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaPdfService;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/aparatura-pomiarowa/reviews')]
class AparaturaPomiarowaReviewController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private AuditService $auditService,
        private AparaturaPomiarowaService $aparaturaService,
        private AparaturaPomiarowaReviewService $reviewService,
        private AparaturaPomiarowaPdfService $pdfService,
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

        $reviewsPagination = $this->reviewService->getReviewsWithPagination($page, 25, $filters);
        $statistics = $this->reviewService->getReviewStatistics();
        
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
        // Ustawienie domyślnej daty planowanej kalibracji na dziś
        $review->setPlannedDate(new \DateTime());
        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sprawdzenie czy wybrano urządzenie lub zestaw
                if (!$review->getEquipment() && !$review->getEquipmentSet()) {
                    $this->addFlash('error', 'Musisz wybrać urządzenie lub zestaw aparatury do kalibracji.');
                    return $this->render('aparatura-pomiarowa/review/new.html.twig', [
                        'form' => $form->createView(),
                        'review' => $review,
                        'page_title' => 'Nowa kalibracja',
                    ]);
                }

                // Pobranie wybranych urządzeń z zestawu (jeśli dotyczy)
                $selectedEquipmentIds = [];
                if ($review->getEquipmentSet()) {
                    $selectedEquipmentIds = $request->get('selected_equipment_ids', []);
                }

                $review = $this->reviewService->createReview($review, $user, $selectedEquipmentIds);
                
                $this->addFlash('success', 'Kalibracja została utworzona pomyślnie z numerem: ' . $review->getReviewNumber());
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
                foreach ($e->getViolations() as $violation) {
                    $this->addFlash('error', $violation->getPropertyPath() . ': ' . $violation->getMessage());
                }
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas tworzenia kalibracji.');
                $this->logger->error('Review creation error', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/new.html.twig', [
            'form' => $form->createView(),
            'review' => $review,
            'page_title' => 'Nowa kalibracja',
        ]);
    }

    #[Route('/{id}', name: 'aparatura_pomiarowa_review_show', requirements: ['id' => '\d+'])]
    public function show(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie uprawnień do różnych akcji
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
    public function edit(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        // Sprawdzenie czy można edytować
        if (!$review->canBeEdited()) {
            $this->addFlash('error', 'Kalibracja w obecnym stanie nie może być edytowana: ' . $review->getStatusDisplayName());
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        $form = $this->createForm(AparaturaPomiarowaReviewType::class, $review);
        
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $selectedEquipmentIds = [];
                if ($review->getEquipmentSet()) {
                    $selectedEquipmentIds = $request->get('selected_equipment_ids', []);
                }

                $this->reviewService->updateReview($review, $user, $selectedEquipmentIds);
                
                $this->addFlash('success', 'Kalibracja została zaktualizowana pomyślnie.');
                return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas aktualizacji kalibracji.');
                $this->logger->error('Review update error', [
                    'review_id' => $review->getId(),
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/review/edit.html.twig', [
            'form' => $form->createView(),
            'review' => $review,
            'page_title' => 'Edycja kalibracji',
        ]);
    }

    #[Route('/{id}/delete', name: 'aparatura_pomiarowa_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('delete_review_' . $review->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        try {
            $this->reviewService->deleteReview($review, $user);
            $this->addFlash('success', 'Kalibracja została usunięta pomyślnie.');
            
            return $this->redirectToRoute('aparatura_pomiarowa_review_index');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania kalibracji.');
            $this->logger->error('Review deletion error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/prepare', name: 'aparatura_pomiarowa_review_prepare', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function prepare(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('prepare_review_' . $review->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        try {
            $this->reviewService->prepareReview($review, $user);
            $this->addFlash('success', 'Kalibracja została przygotowana. Powiadomienie zostało wysłane do odpowiedzialnej osoby.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przygotowywania kalibracji.');
            $this->logger->error('Review preparation error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/send', name: 'aparatura_pomiarowa_review_send', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function send(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('send_review_' . $review->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        try {
            $this->reviewService->sendReview($review, $user);
            $this->addFlash('success', 'Kalibracja została wysłana do laboratorium.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas wysyłania kalibracji.');
            $this->logger->error('Review sending error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/complete', name: 'aparatura_pomiarowa_review_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function complete(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('complete_review_' . $review->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        try {
            $result = $request->get('result', '');
            $notes = $request->get('notes', '');
            $nextReviewDate = null;
            
            $nextReviewDateStr = $request->get('next_review_date');
            if ($nextReviewDateStr) {
                $nextReviewDate = new \DateTime($nextReviewDateStr);
            }

            // Obsługa załączników
            $attachments = [];
            if ($request->files->has('attachments')) {
                foreach ($request->files->get('attachments') as $file) {
                    if ($file instanceof UploadedFile) {
                        $attachments[] = $file;
                    }
                }
            }

            $this->reviewService->completeReview($review, $user, $result, $notes, $nextReviewDate, $attachments);
            $this->addFlash('success', 'Kalibracja została zakończona pomyślnie.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas kończenia kalibracji.');
            $this->logger->error('Review completion error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/cancel', name: 'aparatura_pomiarowa_review_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('cancel_review_' . $review->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }

        try {
            $reason = $request->get('cancel_reason', '');
            $this->reviewService->cancelReview($review, $user, $reason);
            $this->addFlash('success', 'Kalibracja została anulowana.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas anulowania kalibracji.');
            $this->logger->error('Review cancellation error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
    }

    #[Route('/{id}/generate-protocol', name: 'aparatura_pomiarowa_review_generate_protocol', requirements: ['id' => '\d+'])]
    public function generateProtocol(AparaturaPomiarowaReview $review, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        try {
            $pdfContent = $this->pdfService->generateCalibrationProtocolPDF($review);
            $filename = 'kalibracja_' . $review->getReviewNumber() . '.pdf';

            // Audit
            $this->auditService->logUserAction($user, 'generate_aparatura_pomiarowa_calibration_protocol', [
                'review_id' => $review->getId(),
                'review_number' => $review->getReviewNumber()
            ], $request);

            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
            return $response;
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas generowania protokołu PDF.');
            $this->logger->error('Calibration protocol PDF generation error', [
                'review_id' => $review->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return $this->redirectToRoute('aparatura_pomiarowa_review_show', ['id' => $review->getId()]);
        }
    }

    #[Route('/{id}/attachment/{filename}', name: 'aparatura_pomiarowa_review_download_attachment')]
    public function downloadAttachment(AparaturaPomiarowaReview $review, string $filename, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdź czy załącznik należy do tej kalibracji
        if (!in_array($filename, $review->getAttachments())) {
            throw $this->createNotFoundException('Załącznik nie został znaleziony.');
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/reviews/';
        $filePath = $uploadDir . $filename;
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik nie został znaleziony na dysku.');
        }

        // Audit
        $this->auditService->logUserAction($user, 'download_aparatura_pomiarowa_review_attachment', [
            'review_id' => $review->getId(),
            'filename' => $filename
        ], $request);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );
        
        return $response;
    }

    #[Route('/search', name: 'aparatura_pomiarowa_review_search', methods: ['GET'])]
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
            $results = $this->reviewService->searchReviews($query, $limit);
            
            $response = array_map(function($review) {
                return [
                    'id' => $review->getId(),
                    'review_number' => $review->getReviewNumber(),
                    'status' => $review->getStatusDisplayName(),
                    'planned_date' => $review->getPlannedDate() ? $review->getPlannedDate()->format('d.m.Y') : null,
                    'equipment_name' => $review->getEquipment() ? $review->getEquipment()->getName() : null,
                    'set_name' => $review->getEquipmentSet() ? $review->getEquipmentSet()->getName() : null,
                    'url' => $this->generateUrl('aparatura_pomiarowa_review_show', ['id' => $review->getId()])
                ];
            }, $results);
            
            return new JsonResponse($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Review search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd podczas wyszukiwania'], 500);
        }
    }

    #[Route('/overdue', name: 'aparatura_pomiarowa_review_overdue')]
    public function overdueReviews(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $overdueReviews = $this->reviewService->getOverdueReviews();
        $upcomingReviews = $this->reviewService->getUpcomingReviews();
        
        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_overdue_reviews', [
            'overdue_count' => count($overdueReviews),
            'upcoming_count' => count($upcomingReviews)
        ], $request);
        
        return $this->render('aparatura-pomiarowa/review/overdue.html.twig', [
            'overdue_reviews' => $overdueReviews,
            'upcoming_reviews' => $upcomingReviews,
        ]);
    }

    #[Route('/new/equipment/{id}', name: 'aparatura_pomiarowa_review_new_for_equipment', requirements: ['id' => '\d+'])]
    public function newForEquipment(int $id, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'REVIEW', $request);
        
        $equipment = $this->aparaturaService->getEquipment($id);
        if (!$equipment) {
            throw $this->createNotFoundException('Miernik nie został znaleziony');
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

                // Utworzenie kalibracji przez ReviewService
                $review = $this->reviewService->createEquipmentReview($equipment, $data, $user);

                $this->addFlash('success', 'Kalibracja dla miernika została utworzona pomyślnie.');
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
        
        return $this->render('aparatura-pomiarowa/review/edit.html.twig', [
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
        
        $equipmentSet = $this->aparaturaService->getEquipmentSet($id);
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

                // Utworzenie kalibracji przez ReviewService
                $review = $this->reviewService->createEquipmentSetReview($equipmentSet, $data, $user);

                $this->addFlash('success', 'Kalibracja dla zestawu została utworzona pomyślnie.');
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
        
        return $this->render('aparatura-pomiarowa/review/edit.html.twig', [
            'review' => $review,
            'form' => $form,
            'equipment_set' => $equipmentSet,
            'page_title' => 'Nowa kalibracja - ' . $equipmentSet->getName(),
            'can_edit' => true,
            'can_delete' => false
        ]);
    }
}