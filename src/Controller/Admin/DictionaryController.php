<?php

namespace App\Controller\Admin;

use App\Entity\Dictionary;
use App\Repository\DictionaryRepository;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/dictionaries')]
class DictionaryController extends AbstractController
{
    public function __construct(
        private PermissionService $permissionService,
        private DictionaryRepository $dictionaryRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'admin_dictionaries')]
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        $types = $this->dictionaryRepository->findAllTypes();
        $typeCounts = [];
        
        foreach ($types as $type) {
            $typeCounts[$type] = $this->dictionaryRepository->countByType($type);
        }

        return $this->render('admin/dictionaries/index.html.twig', [
            'types' => $types,
            'typeCounts' => $typeCounts,
        ]);
    }

    #[Route('/type/{type}', name: 'admin_dictionaries_type')]
    public function viewType(string $type): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        $dictionaries = $this->dictionaryRepository->findWithChildrenByType($type, false);

        return $this->render('admin/dictionaries/type.html.twig', [
            'type' => $type,
            'dictionaries' => $dictionaries,
        ]);
    }

    #[Route('/new/{type}', name: 'admin_dictionaries_new')]
    public function new(Request $request, string $type): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        if ($request->isMethod('POST')) {
            try {
                $dictionary = new Dictionary();
                $dictionary->setType($type);
                $dictionary->setName($request->request->get('name'));
                $dictionary->setValue($request->request->get('value') ?: $request->request->get('name'));
                $dictionary->setDescription($request->request->get('description'));
                $dictionary->setColor($request->request->get('color'));
                $dictionary->setIcon($request->request->get('icon'));
                $dictionary->setSortOrder((int)$request->request->get('sortOrder', 0));
                $dictionary->setIsActive($request->request->has('isActive'));
                
                // Handle parent
                $parentId = $request->request->get('parent');
                if ($parentId) {
                    $parent = $this->dictionaryRepository->find($parentId);
                    $dictionary->setParent($parent);
                }

                $this->dictionaryRepository->save($dictionary, true);

                $this->logger->info('Dictionary entry created', [
                    'type' => $type,
                    'name' => $dictionary->getName(),
                    'value' => $dictionary->getValue(),
                    'user' => $user->getUsername(),
                    'ip' => $request->getClientIp()
                ]);

                $this->addFlash('success', 'Pozycja słownikowa została dodana.');
                return $this->redirectToRoute('admin_dictionaries_type', ['type' => $type]);
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to create dictionary entry', [
                    'type' => $type,
                    'error' => $e->getMessage(),
                    'user' => $user->getUsername(),
                    'ip' => $request->getClientIp()
                ]);
                
                $this->addFlash('error', 'Wystąpił błąd podczas dodawania pozycji słownikowej.');
            }
        }

        $parentOptions = $this->dictionaryRepository->findRootLevelByType($type, false);

        return $this->render('admin/dictionaries/form.html.twig', [
            'type' => $type,
            'dictionary' => null,
            'parentOptions' => $parentOptions,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_dictionaries_edit')]
    public function edit(Request $request, Dictionary $dictionary): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        if ($request->isMethod('POST')) {
            $dictionary->setName($request->request->get('name'));
            $dictionary->setValue($request->request->get('value') ?: $request->request->get('name'));
            $dictionary->setDescription($request->request->get('description'));
            $dictionary->setColor($request->request->get('color'));
            $dictionary->setIcon($request->request->get('icon'));
            $dictionary->setSortOrder((int)$request->request->get('sortOrder', 0));
            $dictionary->setIsActive($request->request->has('isActive'));
            
            // Handle parent
            $parentId = $request->request->get('parent');
            if ($parentId && $parentId != $dictionary->getId()) {
                $parent = $this->dictionaryRepository->find($parentId);
                $dictionary->setParent($parent);
            } else {
                $dictionary->setParent(null);
            }

            $this->dictionaryRepository->save($dictionary, true);

            $this->addFlash('success', 'Pozycja słownikowa została zaktualizowana.');
            return $this->redirectToRoute('admin_dictionaries_type', ['type' => $dictionary->getType()]);
        }

        $parentOptions = $this->dictionaryRepository->findRootLevelByType($dictionary->getType(), false);

        return $this->render('admin/dictionaries/form.html.twig', [
            'type' => $dictionary->getType(),
            'dictionary' => $dictionary,
            'parentOptions' => $parentOptions,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_dictionaries_delete', methods: ['POST'])]
    public function delete(Dictionary $dictionary): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        if ($dictionary->isSystem()) {
            $this->addFlash('error', 'Nie można usunąć pozycji systemowej.');
            return $this->redirectToRoute('admin_dictionaries_type', ['type' => $dictionary->getType()]);
        }

        $type = $dictionary->getType();
        $this->dictionaryRepository->remove($dictionary, true);

        $this->addFlash('success', 'Pozycja słownikowa została usunięta.');
        return $this->redirectToRoute('admin_dictionaries_type', ['type' => $type]);
    }

    #[Route('/{id}/toggle-status', name: 'admin_dictionaries_toggle_status', methods: ['POST'])]
    public function toggleStatus(Dictionary $dictionary): Response
    {
        $user = $this->getUser();
        
        if (!$this->permissionService->canAccessModule($user, 'admin')) {
            throw $this->createAccessDeniedException('Brak dostępu do panelu administracyjnego');
        }

        $dictionary->setIsActive(!$dictionary->isActive());
        $this->dictionaryRepository->save($dictionary, true);

        $status = $dictionary->isActive() ? 'aktywowana' : 'dezaktywowana';
        $this->addFlash('success', "Pozycja słownikowa została {$status}.");
        
        return $this->redirectToRoute('admin_dictionaries_type', ['type' => $dictionary->getType()]);
    }

    #[Route('/api/type/{type}', name: 'api_dictionaries_by_type', methods: ['GET'])]
    public function apiGetByType(string $type): JsonResponse
    {
        $dictionaries = $this->dictionaryRepository->findByType($type);
        
        $data = array_map(function($dictionary) {
            return [
                'id' => $dictionary->getId(),
                'name' => $dictionary->getName(),
                'value' => $dictionary->getValue(),
                'description' => $dictionary->getDescription(),
                'color' => $dictionary->getColor(),
                'icon' => $dictionary->getIcon(),
                'sortOrder' => $dictionary->getSortOrder(),
            ];
        }, $dictionaries);

        return $this->json($data);
    }
}