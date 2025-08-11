<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\LdapException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\LdapInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LdapService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private LoggerInterface $logger,
        private AuditService $auditService,
        private CacheInterface $ldapCache
    ) {
    }

    /**
     * Testuje połączenie LDAP z podanymi ustawieniami
     */
    public function testConnection(array $settings): array
    {
        try {
            $ldap = $this->createLdapConnection($settings);

            // Bind z użytkownikiem service
            $searchDn = $settings['ldap_search_dn'] ?? '';
            $searchPassword = $settings['ldap_search_password'] ?? '';

            if (!$searchDn || !$searchPassword) {
                throw new LdapException('Nieprawidłowa konfiguracja LDAP - brak danych service user');
            }

            $ldap->bind($searchDn, $searchPassword);

            // Testuj wyszukiwanie użytkowników
            $query = $ldap->query(
                $settings['ldap_base_dn'],
                $settings['ldap_user_filter'],
                ['maxItems' => 5, 'timeout' => 30]
            );

            $result = $query->execute();
            $userCount = count($result);

            $this->logger->info('LDAP connection test successful', [
                'server' => $settings['ldap_host'],
                'users_found' => $userCount
            ]);

            return [
                'success' => true,
                'message' => 'Połączenie LDAP działa poprawnie',
                'details' => [
                    'server' => $settings['ldap_host'],
                    'port' => $settings['ldap_port'],
                    'users_found' => $userCount,
                    'search_base' => $settings['ldap_base_dn'],
                    'connection_time' => new \DateTime()
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('LDAP connection test failed', [
                'server' => $settings['ldap_host'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw new LdapException(
                'Test połączenia LDAP nie powiódł się: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Synchronizuje istniejących użytkowników z LDAP
     */
    public function syncExistingUsers(array $settings): array
    {
        try {
            $ldap = $this->createLdapConnection($settings);
            $this->bindServiceUser($ldap, $settings);

            // Pobierz wszystkich użytkowników z LDAP
            $query = $ldap->query(
                $settings['ldap_base_dn'],
                $settings['ldap_user_filter'],
                ['maxItems' => 5000, 'timeout' => 60]
            );

            $ldapResults = $query->execute();

            // Pobierz wszystkich istniejących użytkowników z bazy
            $existingUsers = $this->userRepository->findAll();
            $usersMap = [];
            foreach ($existingUsers as $user) {
                $usersMap[$user->getUsername()] = $user;
            }

            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($ldapResults as $ldapUser) {
                try {
                    $username = $ldapUser->getAttribute($settings['ldap_username_attribute'])[0] ?? null;
                    if (!$username || !isset($usersMap[$username])) {
                        $skipped++;
                        continue;
                    }

                    $user = $usersMap[$username];
                    $wasUpdated = $this->updateUserFromLdap($user, $ldapUser, $settings);

                    if ($wasUpdated) {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Błąd dla użytkownika {$username}: " . $e->getMessage();
                    $this->logger->error('Error updating user from LDAP', [
                        'username' => $username ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Synchronizacja zakończona',
                'details' => [
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'error_messages' => $errors
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('LDAP sync existing users failed', ['error' => $e->getMessage()]);
            throw new LdapException('Synchronizacja użytkowników nie powiodła się: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Importuje nowych użytkowników z LDAP
     */
    public function syncNewUsers(array $settings): array
    {
        try {
            $ldap = $this->createLdapConnection($settings);
            $this->bindServiceUser($ldap, $settings);

            // Pobierz wszystkich użytkowników z LDAP
            $query = $ldap->query(
                $settings['ldap_base_dn'],
                $settings['ldap_user_filter'],
                ['maxItems' => 5000, 'timeout' => 60]
            );

            $ldapResults = $query->execute();

            // Pobierz listę istniejących usernames z bazy
            $existingUsernames = [];
            $existingUsers = $this->userRepository->findAll();
            foreach ($existingUsers as $user) {
                $existingUsernames[] = $user->getUsername();
            }

            $created = 0;
            $skipped = 0;
            $errors = [];

            foreach ($ldapResults as $ldapUser) {
                try {
                    $username = $ldapUser->getAttribute($settings['ldap_username_attribute'])[0] ?? null;
                    if (!$username || in_array($username, $existingUsernames)) {
                        $skipped++;
                        continue;
                    }

                    $user = $this->createUserFromLdap($ldapUser, $settings);
                    $this->entityManager->persist($user);
                    $created++;

                    // Dodaj do listy istniejących, żeby uniknąć duplikatów
                    $existingUsernames[] = $username;
                } catch (\Exception $e) {
                    $errors[] = "Błąd dla użytkownika {$username}: " . $e->getMessage();
                    $this->logger->error('Error creating user from LDAP', [
                        'username' => $username ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Import nowych użytkowników zakończony',
                'details' => [
                    'created' => $created,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'error_messages' => $errors
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('LDAP sync new users failed', ['error' => $e->getMessage()]);
            throw new LdapException('Import nowych użytkowników nie powiódł się: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Synchronizuje hierarchię przełożonych z LDAP
     */
    public function syncManagerHierarchy(array $settings): array
    {
        try {
            $ldap = $this->createLdapConnection($settings);
            $this->bindServiceUser($ldap, $settings);

            // Pobierz wszystkich użytkowników z LDAP wraz z informacjami o przełożonych
            $query = $ldap->query(
                $settings['ldap_base_dn'],
                $settings['ldap_user_filter'],
                ['maxItems' => 5000, 'timeout' => 60]
            );

            $ldapResults = $query->execute();

            // Utwórz mapę DN -> User
            $allUsers = $this->userRepository->findAll();
            $usersByDn = [];
            foreach ($allUsers as $user) {
                if ($user->getLdapDn()) {
                    $usersByDn[$user->getLdapDn()] = $user;
                }
            }

            $updated = 0;
            $skipped = 0;
            $errors = [];

            foreach ($ldapResults as $ldapUser) {
                try {
                    $userDn = $ldapUser->getDn();
                    if (!isset($usersByDn[$userDn])) {
                        $skipped++;
                        continue;
                    }

                    $user = $usersByDn[$userDn];
                    $managerDn = $ldapUser->getAttribute('manager')[0] ?? null;

                    if ($managerDn && isset($usersByDn[$managerDn])) {
                        $manager = $usersByDn[$managerDn];
                        if ($user->getSupervisor() !== $manager) {
                            $user->setSupervisor($manager);
                            $updated++;
                        }
                    } elseif ($user->getSupervisor() !== null) {
                        // Usuń przełożonego jeśli nie ma go w LDAP
                        $user->setSupervisor(null);
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Błąd dla użytkownika {$userDn}: " . $e->getMessage();
                    $this->logger->error('Error updating manager hierarchy from LDAP', [
                        'user_dn' => $userDn,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->entityManager->flush();

            return [
                'success' => true,
                'message' => 'Synchronizacja hierarchii zakończona',
                'details' => [
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'error_messages' => $errors
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('LDAP sync manager hierarchy failed', ['error' => $e->getMessage()]);
            throw new LdapException('Synchronizacja hierarchii nie powiodła się: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Pobiera użytkownika z LDAP (z cache)
     */
    public function getLdapUser(string $username, array $settings): ?array
    {
        $cacheKey = sprintf('ldap_user_%s_%s', $settings['ldap_host'], $username);

        return $this->ldapCache->get($cacheKey, function (ItemInterface $item) use ($username, $settings) {
            $item->expiresAfter(900); // 15 minut

            try {
                $ldap = $this->createLdapConnection($settings);
                $this->bindServiceUser($ldap, $settings);

                $query = $ldap->query(
                    $settings['ldap_base_dn'],
                    sprintf("({$settings['ldap_username_attribute']}=%s)", ldap_escape($username, '', LDAP_ESCAPE_FILTER))
                );

                $results = $query->execute();
                if (count($results) > 0) {
                    $ldapUser = $results[0];
                    return [
                        'dn' => $ldapUser->getDn(),
                        'attributes' => $ldapUser->getAttributes()
                    ];
                }

                return null;
            } catch (\Exception $e) {
                $this->logger->error('Error fetching LDAP user', [
                    'username' => $username,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Tworzy połączenie LDAP z podanymi ustawieniami
     */
    public function createLdapConnection(array $settings): LdapInterface
    {
        $host = $settings['ldap_host'] ?? '';
        $port = (int) ($settings['ldap_port'] ?? 389);
        $encryption = $settings['ldap_encryption'] ?? 'none';

        if (!$host) {
            throw new LdapException('Host LDAP nie został określony');
        }

        return Ldap::create('ext_ldap', [
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
            'options' => [
                'protocol_version' => 3,
                'referrals' => false
            ]
        ]);
    }

    /**
     * Binduje service user do LDAP
     */
    private function bindServiceUser(LdapInterface $ldap, array $settings): void
    {
        $searchDn = $settings['ldap_search_dn'] ?? '';
        $searchPassword = $settings['ldap_search_password'] ?? '';

        if (!$searchDn || !$searchPassword) {
            throw new LdapException('Nieprawidłowa konfiguracja LDAP - brak danych service user');
        }

        $ldap->bind($searchDn, $searchPassword);
    }

    /**
     * Aktualizuje użytkownika danymi z LDAP
     */
    private function updateUserFromLdap(User $user, $ldapUser, array $settings): bool
    {
        $updated = false;
        $changes = [];

        // Mapowanie atrybutów
        $attributeMap = [
            'first_name' => $settings['ldap_firstname_attribute'] ?? 'givenName',
            'last_name' => $settings['ldap_lastname_attribute'] ?? 'sn',
            'email' => $settings['ldap_email_attribute'] ?? 'mail',
            'position' => $settings['ldap_position_attribute'] ?? 'title',
            'department' => $settings['ldap_department_attribute'] ?? 'department',
            'phone' => $settings['ldap_phone_attribute'] ?? 'telephoneNumber'
        ];

        foreach ($attributeMap as $userField => $ldapAttribute) {
            $ldapValue = $ldapUser->getAttribute($ldapAttribute)[0] ?? '';
            $currentValue = match ($userField) {
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'email' => $user->getEmail(),
                'position' => $user->getPosition(),
                'department' => $user->getDepartment(),
                'phone' => $user->getPhone(),
                default => null
            };

            if ($ldapValue && $currentValue !== $ldapValue) {
                match ($userField) {
                    'first_name' => $user->setFirstName($ldapValue),
                    'last_name' => $user->setLastName($ldapValue),
                    'email' => $user->setEmail($ldapValue),
                    'position' => $user->setPosition($ldapValue),
                    'department' => $user->setDepartment($ldapValue),
                    'phone' => $user->setPhone($ldapValue)
                };
                $changes[$userField] = ['from' => $currentValue, 'to' => $ldapValue];
                $updated = true;
            }
        }

        // Aktualizuj DN jeśli się zmienił
        $newDn = $ldapUser->getDn();
        if ($user->getLdapDn() !== $newDn) {
            $user->setLdapDn($newDn);
            $changes['ldap_dn'] = ['from' => $user->getLdapDn(), 'to' => $newDn];
            $updated = true;
        }

        if ($updated) {
            $user->setLdapSyncedAt(new \DateTime());
            $this->logger->info('User updated from LDAP', [
                'username' => $user->getUsername(),
                'changes' => $changes
            ]);
        }

        return $updated;
    }

    /**
     * Tworzy nowego użytkownika z danych LDAP
     */
    private function createUserFromLdap($ldapUser, array $settings): User
    {
        $user = new User();

        // Podstawowe atrybuty
        $username = $ldapUser->getAttribute($settings['ldap_username_attribute'])[0] ?? '';
        $firstName = $ldapUser->getAttribute($settings['ldap_firstname_attribute'] ?? 'givenName')[0] ?? '';
        $lastName = $ldapUser->getAttribute($settings['ldap_lastname_attribute'] ?? 'sn')[0] ?? '';
        $email = $ldapUser->getAttribute($settings['ldap_email_attribute'] ?? 'mail')[0] ?? '';

        if (!$username) {
            throw new \InvalidArgumentException('Nie można utworzyć użytkownika bez username');
        }

        $user->setUsername($username);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setEmail($email);
        $user->setLdapDn($ldapUser->getDn());
        $user->setIsLdapUser(true);
        $user->setIsActive(true);
        $user->setLdapSyncedAt(new \DateTime());

        // Dodatkowe atrybuty
        if ($position = $ldapUser->getAttribute($settings['ldap_position_attribute'] ?? 'title')[0] ?? '') {
            $user->setPosition($position);
        }
        if ($department = $ldapUser->getAttribute($settings['ldap_department_attribute'] ?? 'department')[0] ?? '') {
            $user->setDepartment($department);
        }
        if ($phone = $ldapUser->getAttribute($settings['ldap_phone_attribute'] ?? 'telephoneNumber')[0] ?? '') {
            $user->setPhone($phone);
        }

        // Ustaw losowe hasło (będzie używana autentykacja LDAP)
        $randomPassword = bin2hex(random_bytes(16));
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $this->logger->info('User created from LDAP', [
            'username' => $username,
            'email' => $email,
            'dn' => $ldapUser->getDn()
        ]);

        return $user;
    }
}