<?php

namespace App\Security;

use App\Entity\User;
use App\Service\SettingService;
use App\Service\LdapService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class LdapAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private SettingService $settingService,
        private LdapService $ldapService,
        private RateLimiterFactory $loginLimiter,
        private ?LoggerInterface $logger = null
    ) {}

    public function authenticate(Request $request): Passport
    {
        // Rate limiting - sprawdź czy IP nie przekroczył limitu
        $limiter = $this->loginLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            throw new TooManyRequestsHttpException(900, 'Too many login attempts. Please try again in 15 minutes.');
        }
        
        $username = $request->getPayload()->getString('_username');
        $password = $request->getPayload()->getString('_password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);

        return new Passport(
            new UserBadge($username),
            new CustomCredentials(
                function($credentials, User $user) {
                    return $this->checkLdapCredentials($credentials, $user);
                },
                $password
            ),
            [
                new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function checkLdapCredentials(string $password, User $user): bool
    {
        $this->logger?->info('LdapAuthenticator: Checking credentials', ['username' => $user->getUsername()]);

        // Jeśli użytkownik ma LDAP DN, spróbuj uwierzytelnienia LDAP
        if ($user->getLdapDn()) {
            $ldapEnabled = $this->settingService->get('ldap_enabled', '0') === '1';
            if ($ldapEnabled) {
                try {
                    return $this->authenticateWithLdap($user, $password);
                } catch (\Exception $e) {
                    $this->logger?->error('LdapAuthenticator: LDAP auth failed', [
                        'username' => $user->getUsername(),
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Fallback na hasło lokalne jeśli użytkownik ma ustawione
            if ($user->getPassword()) {
                $this->logger?->info('LdapAuthenticator: Falling back to local password', ['username' => $user->getUsername()]);
                return password_verify($password, $user->getPassword());
            }

            return false;
        }

        // Użytkownik lokalny - użyj hasła z bazy
        return password_verify($password, $user->getPassword());
    }

    private function authenticateWithLdap(User $user, string $password): bool
    {
        $this->logger?->info('LdapAuthenticator: Attempting LDAP authentication', ['username' => $user->getUsername()]);

        // Pobierz ustawienia LDAP z bazy danych (tak jak robi reset hasła)
        $settings = [
            'ldap_host' => $this->settingService->get('ldap_host'),
            'ldap_port' => $this->settingService->get('ldap_port', '389'),
            'ldap_encryption' => $this->settingService->get('ldap_encryption', 'none'),
            'ldap_ignore_ssl_cert' => $this->settingService->get('ldap_ignore_ssl_cert', '0') === '1'
        ];

        if (!$settings['ldap_host']) {
            throw new \Exception('LDAP host not configured');
        }

        try {
            // Użyj LdapService do tworzenia połączenia (tak jak reset hasła)
            $ldap = $this->ldapService->createLdapConnection($settings);

            // Spróbuj uwierzytelnić bezpośrednio z DN użytkownika
            $ldap->bind($user->getLdapDn(), $password);
            $this->logger?->info('LdapAuthenticator: LDAP authentication successful', ['username' => $user->getUsername()]);
            return true;
        } catch (\Exception $e) {
            $this->logger?->warning('LdapAuthenticator: LDAP authentication failed', [
                'username' => $user->getUsername(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}