<?php

namespace App\AparaturaPomiarowa\Controller;

use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaTransfer;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipment;
use App\AparaturaPomiarowa\Entity\AparaturaPomiarowaEquipmentSet;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaTransferService;
use App\AparaturaPomiarowa\Service\AparaturaPomiarowaPdfService;
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

#[Route('/aparatura-pomiarowa/transfers')]
class AparaturaPomiarowaTransferController extends AbstractController
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

    #[Route('/', name: 'aparatura_pomiarowa_transfer_index')]
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
            'recipient' => $request->query->get('recipient'),
            'handed_by' => $request->query->get('handed_by'),
            'equipment_id' => $request->query->get('equipment_id'),
            'equipment_set_id' => $request->query->get('equipment_set_id'),
            'overdue' => $request->query->getBoolean('overdue'),
            'sort_by' => $request->query->get('sort_by'),
            'sort_dir' => $request->query->get('sort_dir')
        ];

        $transfersPagination = $this->transferService->getTransfersWithPagination($page, 25, $filters);
        $statistics = $this->transferService->getTransferStatistics();
        
        // Sprawdzenie uprawnień
        $canCreate = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_transfers_index', [
            'page' => $page,
            'filters' => array_filter($filters),
            'total_transfers' => $transfersPagination['total']
        ], $request);
        
        return $this->render('aparatura-pomiarowa/transfer/index.html.twig', [
            'transfers' => $transfersPagination,
            'statistics' => $statistics,
            'filters' => $filters,
            'can_create' => $canCreate,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/new', name: 'aparatura_pomiarowa_transfer_new')]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        $transfer = new AparaturaPomiarowaTransfer();
        $transfer->setTransferDate(new \DateTime());
        
        $form = $this->createForm(AparaturaPomiarowaTransferType::class, $transfer);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Sprawdzenie czy wybrano urządzenie lub zestaw
                if (!$transfer->getEquipment() && !$transfer->getEquipmentSet()) {
                    $this->addFlash('error', 'Musisz wybrać urządzenie lub zestaw aparatury do przekazania.');
                    return $this->render('aparatura-pomiarowa/transfer/new.html.twig', [
                        'form' => $form->createView(),
                        'transfer' => $transfer,
                    ]);
                }

                $data = [
                    'recipient' => $transfer->getRecipient(),
                    'transfer_date' => $transfer->getTransferDate(),
                    'return_date' => $transfer->getReturnDate(),
                    'purpose' => $transfer->getPurpose(),
                    'conditions' => $transfer->getConditions(),
                    'location' => $transfer->getLocation(),
                    'notes' => $transfer->getNotes()
                ];

                // Obsługa przekazania pojedynczego urządzenia
                if ($transfer->getEquipment()) {
                    $transfer = $this->transferService->createEquipmentTransfer($transfer->getEquipment(), $data, $user);
                } else {
                    // Obsługa przekazania zestawu
                    $selectedEquipmentIds = $request->get('selected_equipment_ids', []);
                    $transfer = $this->transferService->createEquipmentSetTransfer($transfer->getEquipmentSet(), $data, $user, $selectedEquipmentIds);
                }
                
                $this->addFlash('success', 'Przekazanie aparatury pomiarowej zostało utworzone pomyślnie z numerem: ' . $transfer->getTransferNumber());
                return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
                
            } catch (ValidationException $e) {
                $this->addFlash('error', 'Błędy walidacji: ' . $e->getMessage());
                foreach ($e->getViolations() as $violation) {
                    $this->addFlash('error', $violation->getPropertyPath() . ': ' . $violation->getMessage());
                }
            } catch (BusinessLogicException $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Exception $e) {
                $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas tworzenia przekazania.');
                $this->logger->error('Transfer creation error', [
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername()
                ]);
            }
        }
        
        return $this->render('aparatura-pomiarowa/transfer/new.html.twig', [
            'form' => $form->createView(),
            'transfer' => $transfer,
        ]);
    }

    #[Route('/{id}', name: 'aparatura_pomiarowa_transfer_show', requirements: ['id' => '\d+'])]
    public function show(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie czy użytkownik może widzieć to przekazanie
        if (!$this->canUserViewTransfer($user, $transfer)) {
            throw $this->createAccessDeniedException('Brak uprawnień do wyświetlenia tego przekazania.');
        }

        // Sprawdzenie uprawnień do różnych akcji
        $canEdit = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER');
        $canDelete = $this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'DELETE');

        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_transfer', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber()
        ], $request);
        
        return $this->render('aparatura-pomiarowa/transfer/show.html.twig', [
            'transfer' => $transfer,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
        ]);
    }

    #[Route('/{id}/generate-protocol', name: 'aparatura_pomiarowa_transfer_generate_protocol', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function generateProtocol(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('generate_protocol_' . $transfer->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            $this->transferService->generateProtocol($transfer, $user);
            $this->addFlash('success', 'Protokół przekazania został wygenerowany pomyślnie.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas generowania protokołu.');
            $this->logger->error('Transfer protocol generation error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/upload-protocol', name: 'aparatura_pomiarowa_transfer_upload_protocol', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function uploadProtocol(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('upload_protocol_' . $transfer->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            $protocolFile = $request->files->get('protocol_file');
            if (!$protocolFile || !$protocolFile->isValid()) {
                throw new BusinessLogicException('Wymagany jest poprawny plik protokołu PDF.');
            }
            
            // Walidacja typu pliku
            if ($protocolFile->getMimeType() !== 'application/pdf') {
                throw new BusinessLogicException('Protokół musi być w formacie PDF.');
            }
            
            // Walidacja rozmiaru pliku (10MB max)
            if ($protocolFile->getSize() > 10 * 1024 * 1024) {
                throw new BusinessLogicException('Plik protokołu jest za duży (maksymalnie 10MB).');
            }
            
            // Upload pliku protokołu
            $filename = 'signed_protocol_' . $transfer->getTransferNumber() . '_' . uniqid() . '.pdf';
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/transfers/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $protocolFile->move($uploadDir, $filename);
            
            $this->transferService->uploadProtocolScan($transfer, $filename, $user);
            $this->addFlash('success', 'Skan protokołu został przesłany pomyślnie. Aparatura została przypisana do odbiorcy.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas przesyłania protokołu.');
            $this->logger->error('Transfer protocol upload error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/complete', name: 'aparatura_pomiarowa_transfer_complete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function complete(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('complete_transfer_' . $transfer->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            // Obsługa protokołu zwrotu (opcjonalnie)
            $returnProtocolFile = $request->files->get('return_protocol_file');
            if ($returnProtocolFile && $returnProtocolFile->isValid()) {
                // Walidacja pliku zwrotu
                if ($returnProtocolFile->getMimeType() !== 'application/pdf') {
                    throw new BusinessLogicException('Protokół zwrotu musi być w formacie PDF.');
                }
                
                if ($returnProtocolFile->getSize() > 10 * 1024 * 1024) {
                    throw new BusinessLogicException('Plik protokołu zwrotu jest za duży (maksymalnie 10MB).');
                }
                
                // Upload pliku protokołu zwrotu
                $filename = 'return_protocol_' . $transfer->getTransferNumber() . '_' . uniqid() . '.pdf';
                $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/transfers/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $returnProtocolFile->move($uploadDir, $filename);
                $transfer->setReturnProtocolScanFilename($filename);
            }

            // Dodanie uwag do zwrotu
            $returnNotes = $request->get('return_notes', '');
            if ($returnNotes) {
                $transfer->setReturnNotes($returnNotes);
            }

            $this->transferService->completeTransfer($transfer, $user);
            $this->addFlash('success', 'Przekazanie zostało zakończone pomyślnie. Aparatura została zwrócona.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas kończenia przekazania.');
            $this->logger->error('Transfer completion error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/cancel', name: 'aparatura_pomiarowa_transfer_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancel(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('cancel_transfer_' . $transfer->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            $reason = $request->get('cancel_reason', '');
            $this->transferService->cancelTransfer($transfer, $user, $reason);
            $this->addFlash('success', 'Przekazanie zostało anulowane.');
            
        } catch (BusinessLogicException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas anulowania przekazania.');
            $this->logger->error('Transfer cancellation error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/delete', name: 'aparatura_pomiarowa_transfer_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'DELETE', $request);
        
        // Sprawdzenie CSRF
        if (!$this->isCsrfTokenValid('delete_transfer_' . $transfer->getId(), $request->get('_token'))) {
            $this->addFlash('error', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }

        try {
            // Usunięcie plików protokołów z dysku
            $uploadDir = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/transfers/';
            
            if ($transfer->hasProtocolScan()) {
                $protocolPath = $uploadDir . $transfer->getProtocolScanFilename();
                if (file_exists($protocolPath)) {
                    unlink($protocolPath);
                }
            }
            
            if ($transfer->hasReturnProtocolScan()) {
                $returnProtocolPath = $uploadDir . $transfer->getReturnProtocolScanFilename();
                if (file_exists($returnProtocolPath)) {
                    unlink($returnProtocolPath);
                }
            }

            $this->entityManager->remove($transfer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Przekazanie zostało usunięte pomyślnie.');
            
            // Audit
            $this->auditService->logUserAction($user, 'delete_aparatura_pomiarowa_transfer', [
                'transfer_id' => $transfer->getId(),
                'transfer_number' => $transfer->getTransferNumber()
            ], $request);
            
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_index');
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił nieoczekiwany błąd podczas usuwania przekazania.');
            $this->logger->error('Transfer deletion error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
        }

        return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
    }

    #[Route('/{id}/protocol/download', name: 'aparatura_pomiarowa_transfer_protocol_download', requirements: ['id' => '\d+'])]
    public function downloadProtocol(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie czy użytkownik może pobrać protokół
        if (!$this->canUserViewTransfer($user, $transfer)) {
            throw $this->createAccessDeniedException('Brak uprawnień do pobrania tego protokołu.');
        }
        
        if (!$transfer->hasProtocolScan()) {
            throw $this->createNotFoundException('Protokół nie został jeszcze wygenerowany lub przesłany.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/transfers/' . $transfer->getProtocolScanFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik protokołu nie został znaleziony na dysku.');
        }

        // Audit
        $this->auditService->logUserAction($user, 'download_aparatura_pomiarowa_transfer_protocol', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber()
        ], $request);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'protokol_' . $transfer->getTransferNumber() . '.pdf'
        );
        
        return $response;
    }

    #[Route('/{id}/return-protocol/download', name: 'aparatura_pomiarowa_transfer_return_protocol_download', requirements: ['id' => '\d+'])]
    public function downloadReturnProtocol(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        // Sprawdzenie czy użytkownik może pobrać protokół
        if (!$this->canUserViewTransfer($user, $transfer)) {
            throw $this->createAccessDeniedException('Brak uprawnień do pobrania tego protokołu.');
        }
        
        if (!$transfer->hasReturnProtocolScan()) {
            throw $this->createNotFoundException('Protokół zwrotu nie został jeszcze przesłany.');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/var/uploads/aparatura-pomiarowa/transfers/' . $transfer->getReturnProtocolScanFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Plik protokołu zwrotu nie został znaleziony na dysku.');
        }

        // Audit
        $this->auditService->logUserAction($user, 'download_aparatura_pomiarowa_transfer_return_protocol', [
            'transfer_id' => $transfer->getId(),
            'transfer_number' => $transfer->getTransferNumber()
        ], $request);

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'protokol_zwrotu_' . $transfer->getTransferNumber() . '.pdf'
        );
        
        return $response;
    }

    #[Route('/{id}/generate-pdf-protocol', name: 'aparatura_pomiarowa_transfer_generate_pdf_protocol', requirements: ['id' => '\d+'])]
    public function generatePdfProtocol(AparaturaPomiarowaTransfer $transfer, Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkPermission($user, 'aparatura_pomiarowa', 'TRANSFER', $request);
        
        try {
            $pdfContent = $this->pdfService->generateTransferProtocolPDF($transfer);
            $filename = 'protokol_' . $transfer->getTransferNumber() . '.pdf';

            // Audit
            $this->auditService->logUserAction($user, 'generate_aparatura_pomiarowa_transfer_pdf_protocol', [
                'transfer_id' => $transfer->getId(),
                'transfer_number' => $transfer->getTransferNumber()
            ], $request);

            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
            return $response;
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas generowania protokołu PDF.');
            $this->logger->error('Transfer protocol PDF generation error', [
                'transfer_id' => $transfer->getId(),
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return $this->redirectToRoute('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()]);
        }
    }

    #[Route('/search', name: 'aparatura_pomiarowa_transfer_search', methods: ['GET'])]
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
            $results = $this->transferService->searchTransfers($query, $limit);
            
            $response = array_map(function($transfer) {
                return [
                    'id' => $transfer->getId(),
                    'transfer_number' => $transfer->getTransferNumber(),
                    'status' => $transfer->getStatusDisplayName(),
                    'recipient' => $transfer->getRecipient()->getFullName(),
                    'transfer_date' => $transfer->getTransferDate()->format('d.m.Y'),
                    'equipment_name' => $transfer->getEquipment() ? $transfer->getEquipment()->getName() : null,
                    'set_name' => $transfer->getEquipmentSet() ? $transfer->getEquipmentSet()->getName() : null,
                    'url' => $this->generateUrl('aparatura_pomiarowa_transfer_show', ['id' => $transfer->getId()])
                ];
            }, $results);
            
            return new JsonResponse($response);
            
        } catch (\Exception $e) {
            $this->logger->error('Transfer search error', [
                'query' => $query,
                'error' => $e->getMessage(),
                'user' => $user->getUsername()
            ]);
            
            return new JsonResponse(['error' => 'Błąd podczas wyszukiwania'], 500);
        }
    }

    #[Route('/overdue', name: 'aparatura_pomiarowa_transfer_overdue')]
    public function overdueTransfers(Request $request): Response
    {
        $user = $this->getUser();
        
        // Autoryzacja
        $this->authorizationService->checkModuleAccess($user, 'aparatura_pomiarowa', $request);
        
        $overdueTransfers = $this->transferService->getOverdueTransfers();
        $upcomingReturns = $this->transferService->getUpcomingReturns();
        $withoutScan = $this->transferService->getTransfersWithoutProtocolScan();
        
        // Audit
        $this->auditService->logUserAction($user, 'view_aparatura_pomiarowa_overdue_transfers', [
            'overdue_count' => count($overdueTransfers),
            'upcoming_count' => count($upcomingReturns),
            'without_scan_count' => count($withoutScan)
        ], $request);
        
        return $this->render('aparatura-pomiarowa/transfer/overdue.html.twig', [
            'overdue_transfers' => $overdueTransfers,
            'upcoming_returns' => $upcomingReturns,
            'transfers_without_scan' => $withoutScan,
        ]);
    }

    private function canUserViewTransfer(User $user, AparaturaPomiarowaTransfer $transfer): bool
    {
        // Administratorzy i edytorzy mogą widzieć wszystkie przekazania
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'TRANSFER')) {
            return true;
        }

        // Przeglądający mogą widzieć wszystkie przekazania
        if ($this->authorizationService->hasPermission($user, 'aparatura_pomiarowa', 'VIEW')) {
            return true;
        }

        // Użytkownicy mogą widzieć przekazania, w których są odbiorcami lub przekazującymi
        if ($transfer->getRecipient() === $user || $transfer->getHandedBy() === $user) {
            return true;
        }

        return false;
    }
}