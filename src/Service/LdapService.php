<?php

namespace App\Service;

use App\Entity\Dictionary;
use App\Entity\User;
use App\Exception\LdapException;
use App\Repository\DictionaryRepository;
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
        private DictionaryRepository $dictionaryRepository,
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
                    $username = $ldapUser->getAttribute($settings['ldap_map_username'])[0] ?? null;
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
                    $username = $ldapUser->getAttribute($settings['ldap_map_username'])[0] ?? null;
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
                    sprintf("({$settings['ldap_map_username']}=%s)", ldap_escape($username, '', LDAP_ESCAPE_FILTER))
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
            'first_name' => $settings['ldap_map_firstname'] ?? 'givenName',
            'last_name' => $settings['ldap_map_lastname'] ?? 'sn',
            'email' => $settings['ldap_map_email'] ?? 'mail',
            'position' => $settings['ldap_map_position'] ?? 'title',
            'department' => $settings['ldap_map_department'] ?? 'department',
            'branch' => $settings['ldap_map_office'] ?? 'physicalDeliveryOfficeName',
            'phone' => $settings['ldap_map_phone'] ?? 'telephoneNumber'
        ];

        foreach ($attributeMap as $userField => $ldapAttribute) {
            $ldapValue = $ldapUser->getAttribute($ldapAttribute)[0] ?? '';
            $currentValue = match ($userField) {
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'email' => $user->getEmail(),
                'position' => $user->getPosition(),
                'department' => $user->getDepartment(),
                'branch' => $user->getBranch(),
                'phone' => $user->getPhoneNumber(),
                default => null
            };

            if ($ldapValue && $currentValue !== $ldapValue) {
                $this->logger->info('LDAP value changed for user field', [
                    'field' => $userField,
                    'from' => $currentValue,
                    'to' => $ldapValue
                ]);
                
                // Zapewnij istnienie w słownikach przed przypisaniem
                if ($userField === 'department') {
                    $this->logger->info('Ensuring department dictionary value', ['value' => $ldapValue]);
                    $this->ensureDictionaryValue('employee_departments', $ldapValue);
                } elseif ($userField === 'branch') {
                    $this->logger->info('Ensuring branch dictionary value', ['value' => $ldapValue]);
                    $this->ensureDictionaryValue('employee_branches', $ldapValue);
                }
                
                match ($userField) {
                    'first_name' => $user->setFirstName($ldapValue),
                    'last_name' => $user->setLastName($ldapValue),
                    'email' => $user->setEmail($ldapValue),
                    'position' => $user->setPosition($ldapValue),
                    'department' => $user->setDepartment($ldapValue),
                    'branch' => $user->setBranch($ldapValue),
                    'phone' => $user->setPhoneNumber($ldapValue)
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
        $username = $ldapUser->getAttribute($settings['ldap_map_username'])[0] ?? '';
        $firstName = $ldapUser->getAttribute($settings['ldap_map_firstname'] ?? 'givenName')[0] ?? '';
        $lastName = $ldapUser->getAttribute($settings['ldap_map_lastname'] ?? 'sn')[0] ?? '';
        $email = $ldapUser->getAttribute($settings['ldap_map_email'] ?? 'mail')[0] ?? '';

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
        if ($position = $ldapUser->getAttribute($settings['ldap_map_position'] ?? 'title')[0] ?? '') {
            $user->setPosition($position);
        }
        if ($department = $ldapUser->getAttribute($settings['ldap_map_department'] ?? 'department')[0] ?? '') {
            $this->ensureDictionaryValue('employee_departments', $department);
            $user->setDepartment($department);
        }
        if ($branch = $ldapUser->getAttribute($settings['ldap_map_office'] ?? 'physicalDeliveryOfficeName')[0] ?? '') {
            $this->ensureDictionaryValue('employee_branches', $branch);
            $user->setBranch($branch);
        }
        if ($phone = $ldapUser->getAttribute($settings['ldap_map_phone'] ?? 'telephoneNumber')[0] ?? '') {
            $user->setPhoneNumber($phone);
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

    /**
     * Zapewnia że wartość słownika istnieje, tworzy nową jeśli nie
     */
    private function ensureDictionaryValue(string $type, string $value): void
    {
        if (empty($value)) {
            $this->logger->debug('Empty value for dictionary type', ['type' => $type]);
            return;
        }

        $this->logger->info('Checking dictionary value', ['type' => $type, 'value' => $value]);

        // Sprawdź czy wartość już istnieje w słowniku
        $existing = $this->dictionaryRepository
            ->createQueryBuilder('d')
            ->where('d.type = :type')
            ->andWhere('d.value = :value')
            ->setParameter('type', $type)
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$existing) {
            $this->logger->warning('Dictionary value not found, creating new entry', ['type' => $type, 'value' => $value]);
            
            // Utwórz nowy wpis słownika
            $dictionary = new Dictionary();
            $dictionary->setType($type);
            $dictionary->setValue($value);
            $dictionary->setName($value); // Użyj wartości jako nazwa tymczasowo
            $dictionary->setDescription("Automatycznie utworzony podczas synchronizacji LDAP");
            $dictionary->setIsActive(true);
            $dictionary->setIsSystem(false);
            
            // Ustaw sortOrder jako następny w kolejności
            $maxSort = $this->dictionaryRepository
                ->createQueryBuilder('d')
                ->select('MAX(d.sortOrder)')
                ->where('d.type = :type')
                ->setParameter('type', $type)
                ->getQuery()
                ->getSingleScalarResult();
            
            $dictionary->setSortOrder(($maxSort ?? 0) + 1);
            
            // Ustaw domyślne kolory i ikony w zależności od typu
            switch ($type) {
                case 'employee_branches':
                    $dictionary->setColor('#6f42c1');
                    $dictionary->setIcon('ri-building-2-line');
                    break;
                case 'employee_departments':
                    $dictionary->setColor('#6f42c1');
                    $dictionary->setIcon('ri-team-line');
                    break;
                case 'employee_positions':
                    $dictionary->setColor('#20c997');
                    $dictionary->setIcon('ri-user-star-line');
                    break;
                case 'employee_statuses':
                    $dictionary->setColor('#28a745');
                    $dictionary->setIcon('ri-user-line');
                    break;
                default:
                    $dictionary->setColor('#6c757d');
                    $dictionary->setIcon('ri-list-line');
            }

            $this->entityManager->persist($dictionary);
            $this->entityManager->flush();

            $this->logger->info('Auto-created dictionary entry during LDAP sync', [
                'type' => $type,
                'value' => $value,
                'name' => $value
            ]);
        } else {
            $this->logger->debug('Dictionary value already exists', ['type' => $type, 'value' => $value]);
        }
    }
}