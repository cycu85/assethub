<?php

namespace App\Controller;

use App\Form\AvatarUploadType;
use App\Form\ChangePasswordType;
use App\Service\AuditService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    public function __construct(
        private AuditService $auditService,
        private LoggerInterface $logger
    ) {
    }
    #[Route('/profile', name: 'profile')]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $changePasswordForm = null;
        $avatarUploadForm = null;
        
        // Audit profile page access
        $this->auditService->logUserAction($user, 'view_profile', [], $request);
        
        // Tylko dla użytkowników lokalnych (bez LDAP) pokazuj formularz zmiany hasła
        if (!$user->getLdapDn()) {
            $changePasswordForm = $this->createForm(ChangePasswordType::class);
            $changePasswordForm->handleRequest($request);

            if ($changePasswordForm->isSubmitted()) {
                if ($changePasswordForm->isValid()) {
                    $data = $changePasswordForm->getData();
                    
                    // Sprawdź obecne hasło
                    if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                        $this->addFlash('error', 'Obecne hasło jest nieprawidłowe.');
                        
                        // Audit failed password change attempt
                        $this->auditService->logSecurityEvent('password_change_failed_invalid_current', $user, [
                            'reason' => 'invalid_current_password'
                        ], $request);
                    } else {
                        // Ustaw nowe hasło
                        $encodedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
                        $user->setPassword($encodedPassword);
                        
                        $entityManager->persist($user);
                        $entityManager->flush();
                        
                        $this->addFlash('success', 'Hasło zostało pomyślnie zmienione.');
                        
                        // Audit successful password change
                        $this->auditService->logSecurityEvent('password_changed', $user, [
                            'changed_by_user' => true
                        ], $request);
                        
                        return $this->redirectToRoute('profile');
                    }
                } else {
                    // Formularz ma błędy walidacji
                    $this->addFlash('error', 'Formularz zawiera błędy. Sprawdź wprowadzone dane.');
                    
                    $this->auditService->logSecurityEvent('password_change_failed_validation', $user, [
                        'reason' => 'form_validation_errors'
                    ], $request);
                }
            }
        }

        // Formularz uploadu avatara dla wszystkich użytkowników
        $avatarUploadForm = $this->createForm(AvatarUploadType::class);
        $avatarUploadForm->handleRequest($request);

        if ($avatarUploadForm->isSubmitted() && $avatarUploadForm->isValid()) {
            /** @var UploadedFile $avatarFile */
            $avatarFile = $avatarUploadForm->get('avatar')->getData();

            if ($avatarFile) {
                try {
                    $result = $this->handleAvatarUpload($avatarFile, $user, $slugger, $entityManager);
                    
                    if ($result['success']) {
                        $this->addFlash('success', 'Zdjęcie profilowe zostało pomyślnie zmienione.');
                        
                        // Audit successful avatar change
                        $this->auditService->logUserAction($user, 'avatar_changed', [
                            'filename' => $result['filename'],
                            'file_size' => $result['file_size']
                        ], $request);
                        
                        return $this->redirectToRoute('profile');
                    } else {
                        $this->addFlash('error', $result['error']);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Wystąpił błąd podczas przesyłania zdjęcia.');
                    
                    $this->logger->error('Avatar upload failed', [
                        'user' => $user->getUsername(),
                        'error' => $e->getMessage(),
                        'file_name' => $avatarFile->getClientOriginalName()
                    ]);
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'changePasswordForm' => $changePasswordForm?->createView(),
            'avatarUploadForm' => $avatarUploadForm->createView(),
            'isLdapUser' => !empty($user->getLdapDn())
        ]);
    }
    
    private function handleAvatarUpload(UploadedFile $avatarFile, $user, SluggerInterface $slugger, EntityManagerInterface $entityManager): array
    {
        // Validate file type and size
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if ($avatarFile->getSize() > $maxSize) {
            return ['success' => false, 'error' => 'Plik jest za duży. Maksymalny rozmiar to 2MB.'];
        }
        
        $extension = strtolower($avatarFile->guessExtension());
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Niedozwolony typ pliku. Dozwolone: JPG, PNG, GIF.'];
        }
        
        $originalFilename = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;
        
        $uploadsDir = $this->getParameter('kernel.project_dir').'/public/uploads/avatars';
        
        // Ensure directory exists
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        
        try {
            $avatarFile->move($uploadsDir, $newFilename);
            
            // Remove old avatar if exists
            if ($user->getAvatar()) {
                $oldAvatarPath = $uploadsDir.'/'.$user->getAvatar();
                if (file_exists($oldAvatarPath) && $user->getAvatar() !== 'default.png') {
                    unlink($oldAvatarPath);
                }
            }
            
            $user->setAvatar($newFilename);
            $entityManager->persist($user);
            $entityManager->flush();
            
            return [
                'success' => true,
                'filename' => $newFilename,
                'file_size' => filesize($uploadsDir.'/'.$newFilename)
            ];
            
        } catch (FileException $e) {
            return ['success' => false, 'error' => 'Wystąpił błąd podczas przesyłania pliku: ' . $e->getMessage()];
        }
    }
}