<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SettingService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapUserProvider implements UserProviderInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private SettingService $settingService,
        private ?LoggerInterface $logger = null
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $this->logger?->info('LdapUserProvider: Attempting to load user', ['identifier' => $identifier]);
        
        // Najpierw spróbuj znaleźć użytkownika w bazie danych
        $user = $this->userRepository->findOneBy(['username' => $identifier]);
        
        if ($user && $user->getLdapDn()) {
            $this->logger?->info('LdapUserProvider: Found LDAP user in database', ['username' => $identifier, 'ldapDn' => $user->getLdapDn()]);
            
            // Sprawdź czy LDAP jest włączony
            $ldapEnabled = $this->settingService->get('ldap_enabled', '0') === '1';
            if (!$ldapEnabled) {
                $this->logger?->info('LdapUserProvider: LDAP disabled, falling back to local auth', ['username' => $identifier]);
                if ($user->getPassword()) {
                    return $user;
                }
                throw new UserNotFoundException(sprintf('LDAP user "%s" cannot authenticate - LDAP disabled and no local password.', $identifier));
            }
            
            // Użytkownik istnieje i ma DN LDAP - sprawdź czy jest aktywny w LDAP
            try {
                $ldap = $this->createLdapConnection();
                $baseDn = $this->settingService->get('ldap_base_dn');
                $searchDn = $this->settingService->get('ldap_bind_dn');
                $searchPassword = $this->settingService->get('ldap_bind_password');
                $uidKey = $this->settingService->get('ldap_map_username', 'sAMAccountName');
                
                if (!$baseDn || !$searchDn || !$searchPassword) {
                    throw new \Exception('LDAP configuration incomplete');
                }
                
                $ldap->bind($searchDn, $searchPassword);
                
                // Zabezpieczenie przed LDAP injection - escapowanie specjalnych znaków
                $escapedIdentifier = ldap_escape($identifier, '', LDAP_ESCAPE_FILTER);
                $query = $ldap->query($baseDn, "({$uidKey}={$escapedIdentifier})");
                $result = $query->execute();
                
                if ($result->count() > 0) {
                    $this->logger?->info('LdapUserProvider: LDAP user found in directory', ['username' => $identifier]);
                    // Nie uwierzytelniamy hasła tutaj - to robi FormLoginAuthenticator
                    // Tylko sprawdzamy czy użytkownik istnieje w LDAP
                    return $user;
                } else {
                    $this->logger?->warning('LdapUserProvider: LDAP user not found in directory', ['username' => $identifier]);
                }
            } catch (\Exception $e) {
                $this->logger?->error('LdapUserProvider: LDAP error, falling back to local auth', ['username' => $identifier, 'error' => $e->getMessage()]);
                // Błąd LDAP - pozwól na logowanie przez bazę danych jeśli ma hasło
                if ($user->getPassword()) {
                    $this->logger?->info('LdapUserProvider: Using local password fallback', ['username' => $identifier]);
                    return $user;
                }
            }
        } elseif ($user && !$user->getLdapDn()) {
            $this->logger?->info('LdapUserProvider: Found local user in database', ['username' => $identifier]);
            // Użytkownik lokalny (bez LDAP)
            return $user;
        } else {
            $this->logger?->warning('LdapUserProvider: User not found in database', ['username' => $identifier]);
        }

        throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
    }

    private function createLdapConnection(): LdapInterface
    {
        $host = $this->settingService->get('ldap_host');
        $port = (int) $this->settingService->get('ldap_port', '389');
        
        if (!$host) {
            throw new \Exception('LDAP host not configured');
        }
        
        // Usuń ewentualny prefix protokołu
        $host = preg_replace('/^(ldaps?:\/\/)/', '', $host);
        
        $options = [
            'host' => $host,
            'port' => $port,
            'version' => 3,
            'referrals' => false,
        ];
        
        $this->logger?->info('LdapUserProvider: Creating LDAP connection', ['host' => $host, 'port' => $port]);
        
        return Ldap::create('ext_ldap', $options);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }
}