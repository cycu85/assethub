<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HomeController extends AbstractController
{
    public function __construct(
        Environment $twig,
        private LoggerInterface $logger
    ) {
        $this->loader = $twig->getLoader();
    }

    #[Route('/', name: 'home')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        
        $this->logger->info('Home page accessed', [
            'user' => $user?->getUsername() ?? 'anonymous',
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ]);
        
        return $this->render('index.html.twig');
    }

    #[Route('/{path}', requirements: ['path' => '^(?!install|admin|api|login|logout|profile|asekuracja).*'])]
    public function root($path, Request $request)
    {
        $user = $this->getUser();
        
        if ($this->loader->exists($path.'.html.twig')) {
            $this->logger->info('Template page accessed', [
                'user' => $user?->getUsername() ?? 'anonymous',
                'ip' => $request->getClientIp(),
                'path' => $path,
                'template' => $path.'.html.twig'
            ]);
            
            if ($path == '/' || $path == 'home' || $path == 'index') {
                return $this->render('index.html.twig');
            }
            return $this->render($path.'.html.twig');
        }
        
        $this->logger->warning('Template not found', [
            'user' => $user?->getUsername() ?? 'anonymous',
            'ip' => $request->getClientIp(),
            'path' => $path,
            'attempted_template' => $path.'.html.twig'
        ]);
        
        throw $this->createNotFoundException();
    }
}
