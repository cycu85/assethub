<?php

namespace App\Command;

use App\Entity\Notification;
use App\Entity\User;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-notifications',
    description: 'Creates test notifications for all users'
)]
class CreateTestNotificationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'Create notifications only for specific user ID')
            ->addOption('count', 'c', InputOption::VALUE_OPTIONAL, 'Number of notifications to create per user', 5);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $userId = $input->getOption('user-id');
        $count = (int) $input->getOption('count');

        if ($userId) {
            $user = $this->entityManager->getRepository(User::class)->find($userId);
            if (!$user) {
                $io->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            $users = [$user];
        } else {
            $users = $this->entityManager->getRepository(User::class)->findAll();
        }

        if (empty($users)) {
            $io->warning('No users found.');
            return Command::SUCCESS;
        }

        $totalCreated = 0;

        foreach ($users as $user) {
            $created = $this->createNotificationsForUser($user, $count);
            $totalCreated += $created;
            $io->text("Created {$created} notifications for user: {$user->getUsername()}");
        }

        $this->entityManager->flush();

        $io->success("Successfully created {$totalCreated} test notifications.");

        return Command::SUCCESS;
    }

    private function createNotificationsForUser(User $user, int $count): int
    {
        $notifications = [
            [
                'title' => 'Przegląd sprzętu przygotowany',
                'content' => 'Przegląd zestawu "Zestaw podstawowy #001" został przygotowany i oczekuje na dostarczenie.',
                'type' => Notification::TYPE_WARNING,
                'category' => Notification::CATEGORY_REVIEW,
                'action_url' => '/asekuracja/equipment-set/1',
                'action_text' => 'Zobacz szczegóły'
            ],
            [
                'title' => 'Nowe przekazanie sprzętu',
                'content' => 'Otrzymałeś zestaw sprzętu "Zestaw zaawansowany #002" do przekazania.',
                'type' => Notification::TYPE_INFO,
                'category' => Notification::CATEGORY_TRANSFER,
                'action_url' => '/asekuracja/equipment-set/2',
                'action_text' => 'Zobacz szczegóły'
            ],
            [
                'title' => 'Aktualizacja systemu',
                'content' => 'System został zaktualizowany do wersji 2.1.0. Sprawdź nowe funkcjonalności.',
                'type' => Notification::TYPE_SUCCESS,
                'category' => Notification::CATEGORY_SYSTEM,
                'action_url' => '/dashboard',
                'action_text' => 'Przejdź do panelu'
            ],
            [
                'title' => 'Sprzęt wymaga konserwacji',
                'content' => 'Sprzęt "Szelki Petzl AVAO" wymaga planowej konserwacji.',
                'type' => Notification::TYPE_WARNING,
                'category' => Notification::CATEGORY_EQUIPMENT,
                'action_url' => '/asekuracja/equipment/1',
                'action_text' => 'Zobacz sprzęt'
            ],
            [
                'title' => 'Zwrot sprzętu zakończony',
                'content' => 'Zestaw sprzętu "Zestaw specjalistyczny #003" został pomyślnie zwrócony.',
                'type' => Notification::TYPE_SUCCESS,
                'category' => Notification::CATEGORY_TRANSFER,
                'action_url' => '/asekuracja/equipment-set/3',
                'action_text' => 'Zobacz szczegóły'
            ],
            [
                'title' => 'Błąd synchronizacji',
                'content' => 'Wystąpił problem podczas synchronizacji danych. Skontaktuj się z administratorem.',
                'type' => Notification::TYPE_ERROR,
                'category' => Notification::CATEGORY_SYSTEM,
                'action_url' => '/profile',
                'action_text' => 'Skontaktuj się z pomocą'
            ],
            [
                'title' => 'Nowy użytkownik w systemie',
                'content' => 'Do systemu został dodany nowy użytkownik: Jan Kowalski.',
                'type' => Notification::TYPE_INFO,
                'category' => Notification::CATEGORY_USER,
                'action_url' => '/admin/users',
                'action_text' => 'Zarządzaj użytkownikami'
            ],
            [
                'title' => 'Przegląd okresowy zakończony',
                'content' => 'Przegląd okresowy zestawu "Zestaw ratowniczy #004" został pomyślnie zakończony.',
                'type' => Notification::TYPE_SUCCESS,
                'category' => Notification::CATEGORY_REVIEW,
                'action_url' => '/asekuracja/equipment-set/4',
                'action_text' => 'Zobacz raport'
            ],
        ];

        $created = 0;
        for ($i = 0; $i < $count && $i < count($notifications); $i++) {
            $notificationData = $notifications[$i];
            
            $notification = new Notification();
            $notification
                ->setUser($user)
                ->setTitle($notificationData['title'])
                ->setContent($notificationData['content'])
                ->setType($notificationData['type'])
                ->setCategory($notificationData['category'])
                ->setActionUrl($notificationData['action_url'])
                ->setActionText($notificationData['action_text'])
                ->setData([
                    'created_by' => 'test-command',
                    'test_data' => true
                ]);

            // Make some notifications read (50% chance)
            if (rand(1, 100) <= 50) {
                $notification->markAsRead();
            }

            $this->entityManager->persist($notification);
            $created++;
        }

        return $created;
    }
}