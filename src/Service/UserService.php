<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserRole;
use App\Entity\Role;
use App\Exception\BusinessLogicException;
use App\Exception\ValidationException;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\DictionaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private DictionaryRepository $dictionaryRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private AuditService $auditService,
        private LoggerInterface $logger,
        private PaginatorInterface $paginator
    ) {
    }

    /**
     * Pobiera użytkowników z paginacją
     */
    public function getUsersWithPagination(int $page = 1, int $limit = 25, array $filters = []): object
    {
        $queryBuilder = $this->userRepository->createQueryBuilder('u');

        // Zastosuj filtry
        if (!empty($filters['active'])) {
            $queryBuilder->andWhere('u.isActive = :active')
                         ->setParameter('active', $filters['active'] === 'true');
        }

        if (!empty($filters['department'])) {
            $queryBuilder->andWhere('u.department LIKE :department')
                         ->setParameter('department', '%' . $filters['department'] . '%');
        }

        if (!empty($filters['search'])) {
            $queryBuilder->andWhere('u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search')
                         ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $queryBuilder->orderBy('u.lastName', 'ASC')
                     ->addOrderBy('u.firstName', 'ASC');

        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Tworzy nowego użytkownika
     */
    public function createUser(array $userData, User $creator): User
    {
        $user = new User();
        $this->populateUserFromArray($user, $userData);

        // Walidacja
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidationException('Błędy walidacji', $errors);
        }

        // Sprawdź czy username jest unikalny
        if ($this->userRepository->findOneBy(['username' => $user->getUsername()])) {
            throw new BusinessLogicException('Użytkownik o tej nazwie już istnieje');
        }

        // Sprawdź czy email jest unikalny
        if ($user->getEmail() && $this->userRepository->findOneBy(['email' => $user->getEmail()])) {
            throw new BusinessLogicException('Użytkownik o tym adresie email już istnieje');
        }

        // Hashuj hasło jeśli podane
        if (!empty($userData['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Loguj utworzenie
        $this->auditService->logCrudOperation(
            $creator,
            'create',
            'User',
            $user->getId(),
            ['username' => $user->getUsername(), 'email' => $user->getEmail()]
        );

        return $user;
    }

    /**
     * Aktualizuje użytkownika
     */
    public function updateUser(User $user, array $userData, User $updater): User
    {
        $oldData = [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'position' => $user->getPosition(),
            'department' => $user->getDepartment(),
            'isActive' => $user->isActive()
        ];

        $this->populateUserFromArray($user, $userData);

        // Walidacja
        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = $violation->getMessage();
            }
            throw new ValidationException('Błędy walidacji', $errors);
        }

        // Sprawdź unikalność email jeśli się zmienił
        if (isset($userData['email']) && $userData['email'] !== $oldData['email']) {
            $existingUser = $this->userRepository->findOneBy(['email' => $userData['email']]);
            if ($existingUser && $existingUser->getId() !== $user->getId()) {
                throw new BusinessLogicException('Użytkownik o tym adresie email już istnieje');
            }
        }

        // Aktualizuj hasło jeśli podane
        if (!empty($userData['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
        }

        $this->entityManager->flush();

        // Loguj zmiany
        $changes = [];
        foreach ($oldData as $field => $oldValue) {
            $newValue = match ($field) {
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
                'position' => $user->getPosition(),
                'department' => $user->getDepartment(),
                'isActive' => $user->isActive()
            };

            if ($oldValue !== $newValue) {
                $changes[$field] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        if (!empty($changes)) {
            $this->auditService->logCrudOperation(
                $updater,
                'update',
                'User',
                $user->getId(),
                $changes
            );
        }

        return $user;
    }

    /**
     * Zarządza rolami użytkownika
     */
    public function manageUserRoles(User $user, array $roleIds, User $admin): void
    {
        $oldRoles = [];
        foreach ($user->getUserRoles() as $userRole) {
            $oldRoles[] = $userRole->getRole()->getName();
        }

        // Usuń wszystkie obecne role
        foreach ($user->getUserRoles() as $userRole) {
            $this->entityManager->remove($userRole);
        }
        $user->getUserRoles()->clear();

        // Dodaj nowe role
        $newRoles = [];
        foreach ($roleIds as $roleId) {
            $role = $this->roleRepository->find($roleId);
            if (!$role) {
                throw new BusinessLogicException("Rola o ID {$roleId} nie istnieje");
            }

            $userRole = new UserRole();
            $userRole->setUser($user);
            $userRole->setRole($role);
            $user->addUserRole($userRole);
            $this->entityManager->persist($userRole);

            $newRoles[] = $role->getName();
        }

        $this->entityManager->flush();

        // Loguj zmiany ról
        $this->auditService->logRoleChange($admin, $user, $oldRoles, $newRoles);
    }

    /**
     * Deaktywuje użytkownika
     */
    public function deactivateUser(User $user, User $admin, string $reason = ''): void
    {
        if (!$user->isActive()) {
            throw new BusinessLogicException('Użytkownik jest już nieaktywny');
        }

        $user->setIsActive(false);
        $user->setDeactivatedAt(new \DateTime());

        $this->entityManager->flush();

        $this->auditService->logUserAction($admin, 'deactivate_user', [
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername(),
            'reason' => $reason
        ]);
    }

    /**
     * Aktywuje użytkownika
     */
    public function activateUser(User $user, User $admin): void
    {
        if ($user->isActive()) {
            throw new BusinessLogicException('Użytkownik jest już aktywny');
        }

        $user->setIsActive(true);
        $user->setDeactivatedAt(null);

        $this->entityManager->flush();

        $this->auditService->logUserAction($admin, 'activate_user', [
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername()
        ]);
    }

    /**
     * Usuwa użytkownika (soft delete)
     */
    public function deleteUser(User $user, User $admin, string $reason = ''): void
    {
        // Sprawdź czy użytkownik nie ma przypisanych zasobów
        // TODO: Dodać sprawdzenie Equipment, gdy będzie serwis

        $user->setIsActive(false);
        $user->setDeletedAt(new \DateTime());

        $this->entityManager->flush();

        $this->auditService->logCrudOperation(
            $admin,
            'delete',
            'User',
            $user->getId(),
            ['username' => $user->getUsername(), 'reason' => $reason]
        );
    }

    /**
     * Zmienia hasło użytkownika
     */
    public function changePassword(User $user, string $newPassword, User $admin): void
    {
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $user->setPasswordChangedAt(new \DateTime());

        $this->entityManager->flush();

        $this->auditService->logUserAction($admin, 'change_password', [
            'target_user_id' => $user->getId(),
            'target_username' => $user->getUsername()
        ]);
    }

    /**
     * Pobiera słowniki dla formularzy użytkowników
     */
    public function getDictionariesForForms(): array
    {
        return [
            'branches' => $this->dictionaryRepository->findByType('employee_branches'),
            'departments' => $this->dictionaryRepository->findByType('employee_departments'),
            'positions' => $this->dictionaryRepository->findByType('employee_positions'),
            'statuses' => $this->dictionaryRepository->findByType('employee_statuses'),
            'locations' => $this->dictionaryRepository->findByType('locations')
        ];
    }

    /**
     * Wyszukuje użytkowników
     */
    public function searchUsers(string $query, int $limit = 10): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->andWhere('(
                u.firstName LIKE :query OR 
                u.lastName LIKE :query OR 
                u.email LIKE :query OR 
                u.employeeNumber LIKE :query OR
                u.position LIKE :query OR
                u.department LIKE :query
            )')
            ->setParameter('active', true)
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Pobiera statystyki użytkowników
     */
    public function getUserStatistics(): array
    {
        $total = $this->userRepository->createQueryBuilder('u')
                   ->select('COUNT(u.id)')
                   ->getQuery()
                   ->getSingleScalarResult();

        $active = $this->userRepository->createQueryBuilder('u')
                    ->select('COUNT(u.id)')
                    ->where('u.isActive = :active')
                    ->setParameter('active', true)
                    ->getQuery()
                    ->getSingleScalarResult();

        $ldapUsers = $this->userRepository->createQueryBuilder('u')
                       ->select('COUNT(u.id)')
                       ->where('u.ldapDn IS NOT NULL')
                       ->getQuery()
                       ->getSingleScalarResult();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'ldap_users' => $ldapUsers,
            'local_users' => $total - $ldapUsers
        ];
    }

    /**
     * Pobiera użytkowników według działu
     */
    public function getUsersByDepartment(): array
    {
        $results = $this->userRepository->createQueryBuilder('u')
            ->select('u.department, COUNT(u.id) as user_count')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('u.department')
            ->orderBy('user_count', 'DESC')
            ->getQuery()
            ->getResult();

        $departments = [];
        foreach ($results as $result) {
            $departments[$result['department'] ?? 'Nie określono'] = (int) $result['user_count'];
        }

        return $departments;
    }

    /**
     * Wypełnia użytkownika danymi z tablicy
     */
    private function populateUserFromArray(User $user, array $data): void
    {
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['position'])) {
            $user->setPosition($data['position']);
        }
        if (isset($data['department'])) {
            $user->setDepartment($data['department']);
        }
        if (isset($data['phone'])) {
            $user->setPhone($data['phone']);
        }
        if (isset($data['employeeNumber'])) {
            $user->setEmployeeNumber($data['employeeNumber']);
        }
        if (isset($data['isActive'])) {
            $user->setIsActive((bool) $data['isActive']);
        }
        if (isset($data['location'])) {
            $user->setLocation($data['location']);
        }
        if (isset($data['startDate'])) {
            $user->setStartDate($data['startDate'] instanceof \DateTime ? $data['startDate'] : new \DateTime($data['startDate']));
        }
    }
}