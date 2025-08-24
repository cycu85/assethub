<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\AuthorizationService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AuthorizationExtension extends AbstractExtension
{
    public function __construct(
        private AuthorizationService $authorizationService,
        private Security $security
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_permission', [$this, 'hasPermission']),
            new TwigFunction('has_any_permission', [$this, 'hasAnyPermission']),
        ];
    }

    public function hasPermission(string $module, string $permission): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        return $this->authorizationService->hasPermission($user, $module, $permission);
    }

    public function hasAnyPermission(string $module, array $permissions): bool
    {
        $user = $this->security->getUser();
        
        if (!$user instanceof User) {
            return false;
        }

        return $this->authorizationService->checkAnyPermission($user, $module, $permissions);
    }
}