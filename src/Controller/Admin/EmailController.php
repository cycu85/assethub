<?php

namespace App\Controller\Admin;

use App\Service\EmailService;
use App\Repository\EmailHistoryRepository;
use App\Service\AuthorizationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/emails')]
class EmailController extends AbstractController
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private EmailService $emailService,
        private EmailHistoryRepository $emailHistoryRepository
    ) {
    }

    #[Route('/', name: 'admin_emails_index')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->authorizationService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak uprawnień do modułu administracyjnego');
        }

        // Pobierz parametry filtrowania
        $days = $request->query->getInt('days', 30);
        $status = $request->query->get('status', '');
        $type = $request->query->get('type', '');
        $search = $request->query->get('search', '');

        // Pobierz historię maili
        $emails = $this->emailHistoryRepository->findRecentEmails($days);

        // Filtrowanie po statusie
        if ($status) {
            $emails = array_filter($emails, fn($email) => $email->getStatus() === $status);
        }

        // Filtrowanie po typie
        if ($type) {
            $emails = array_filter($emails, fn($email) => $email->getEmailType() === $type);
        }

        // Filtrowanie po wyszukiwanej frazie (email odbiorcy lub temat)
        if ($search) {
            $searchLower = strtolower($search);
            $emails = array_filter($emails, function($email) use ($searchLower) {
                return strpos(strtolower($email->getRecipientEmail()), $searchLower) !== false ||
                       strpos(strtolower($email->getSubject()), $searchLower) !== false;
            });
        }

        // Pobierz statystyki
        $stats = $this->emailService->getEmailStatistics($days);
        $statusCounts = $this->emailHistoryRepository->countByStatus($days);

        return $this->render('admin/emails/index.html.twig', [
            'emails' => $emails,
            'stats' => $stats,
            'status_counts' => $statusCounts,
            'current_days' => $days,
            'current_status' => $status,
            'current_type' => $type,
            'current_search' => $search,
        ]);
    }

    #[Route('/{id}', name: 'admin_emails_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $user = $this->getUser();
        
        if (!$this->authorizationService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak uprawnień do modułu administracyjnego');
        }

        $email = $this->emailHistoryRepository->find($id);
        
        if (!$email) {
            throw $this->createNotFoundException('Nie znaleziono wiadomości email');
        }

        return $this->render('admin/emails/show.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/stats', name: 'admin_emails_stats')]
    public function stats(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->authorizationService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak uprawnień do modułu administracyjnego');
        }

        $days = $request->query->getInt('days', 30);
        
        $stats = $this->emailService->getEmailStatistics($days);
        $statusCounts = $this->emailHistoryRepository->countByStatus($days);
        $recentEmails = $this->emailHistoryRepository->findRecentEmails(7);

        return $this->render('admin/emails/stats.html.twig', [
            'stats' => $stats,
            'status_counts' => $statusCounts,
            'recent_emails' => $recentEmails,
            'current_days' => $days,
        ]);
    }

    #[Route('/cleanup', name: 'admin_emails_cleanup', methods: ['POST'])]
    public function cleanup(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$this->authorizationService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak uprawnień do modułu administracyjnego');
        }

        $days = $request->request->getInt('days', 90);
        
        if ($days < 7) {
            $this->addFlash('error', 'Minimalna liczba dni do przechowywania to 7');
            return $this->redirectToRoute('admin_emails_stats');
        }

        try {
            $deletedCount = $this->emailService->cleanupOldEmails($days);
            
            if ($deletedCount > 0) {
                $this->addFlash('success', "Usunięto {$deletedCount} starych rekordów historii maili");
            } else {
                $this->addFlash('info', 'Nie znaleziono starych rekordów do usunięcia');
            }
            
        } catch (\Exception $e) {
            $this->addFlash('error', 'Błąd podczas czyszczenia: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_emails_stats');
    }
}