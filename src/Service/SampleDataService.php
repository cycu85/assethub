<?php

namespace App\Service;

use App\Entity\EquipmentCategory;
use App\Entity\User;
use App\DataFixtures\AppFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SampleDataService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function loadSampleData(): void
    {
        // Load all fixtures (including asekuracja and aparatura_pomiarowa module data)
        $fixtures = new AppFixtures($this->passwordHasher);
        $fixtures->load($this->entityManager);

        // Create additional sample users (beyond what fixtures provide)
        $this->createSampleUsers();

        // Equipment module disabled - no longer creating legacy equipment data

        $this->entityManager->flush();
    }

    private function createSampleUsers(): array
    {
        $users = [
            [
                'username' => 'j.kowalski',
                'email' => 'jan.kowalski@firma.pl',
                'firstName' => 'Jan',
                'lastName' => 'Kowalski',
                'employeeNumber' => 'EMP001',
                'position' => 'Kierownik Budowy',
                'department' => 'Dział Produkcji',
                'phoneNumber' => '+48 123 456 789',
                'password' => 'haslo123'
            ],
            [
                'username' => 'a.nowak',
                'email' => 'anna.nowak@firma.pl',
                'firstName' => 'Anna',
                'lastName' => 'Nowak',
                'employeeNumber' => 'EMP002',
                'position' => 'Specjalista BHP',
                'department' => 'Dział BHP',
                'phoneNumber' => '+48 123 456 790',
                'password' => 'haslo123'
            ],
            [
                'username' => 'm.wisniewski',
                'email' => 'marek.wisniewski@firma.pl',
                'firstName' => 'Marek',
                'lastName' => 'Wiśniewski',
                'employeeNumber' => 'EMP003',
                'position' => 'Operator Maszyn',
                'department' => 'Dział Produkcji',
                'phoneNumber' => '+48 123 456 791',
                'password' => 'haslo123'
            ],
            [
                'username' => 'k.zielinska',
                'email' => 'katarzyna.zielinska@firma.pl',
                'firstName' => 'Katarzyna',
                'lastName' => 'Zielińska',
                'employeeNumber' => 'EMP004',
                'position' => 'Magazynier',
                'department' => 'Magazyn',
                'phoneNumber' => '+48 123 456 792',
                'password' => 'haslo123'
            ],
            [
                'username' => 'p.kaminski',
                'email' => 'piotr.kaminski@firma.pl',
                'firstName' => 'Piotr',
                'lastName' => 'Kamiński',
                'employeeNumber' => 'EMP005',
                'position' => 'Serwisant',
                'department' => 'Dział Techniczny',
                'phoneNumber' => '+48 123 456 793',
                'password' => 'haslo123'
            ]
        ];

        $createdUsers = [];
        foreach ($users as $userData) {
            $user = new User();
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);
            $user->setFirstName($userData['firstName']);
            $user->setLastName($userData['lastName']);
            $user->setEmployeeNumber($userData['employeeNumber']);
            $user->setPosition($userData['position']);
            $user->setDepartment($userData['department']);
            $user->setPhoneNumber($userData['phoneNumber']);
            $user->setIsActive(true);

            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $createdUsers[] = $user;
        }

        return $createdUsers;
    }

    // Legacy createSampleEquipment method removed - Equipment module disabled

    public function getSampleDataSummary(): array
    {
        return [
            'users' => 8, // admin, hr, user + 5 additional sample users
            'asekuracyjny_equipment' => 3, // From fixtures
            'aparatura_pomiarowa_equipment' => 3, // From fixtures
            'modules' => 'admin, asekuracja, aparatura_pomiarowa',
            'dictionaries' => 'Complete system dictionaries for all modules',
            'categories' => 4,
            'note' => 'Legacy equipment module disabled - replaced with specialized modules'
        ];
    }
}