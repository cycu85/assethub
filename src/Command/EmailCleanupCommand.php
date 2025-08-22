<?php

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:email:cleanup',
    description: 'Czyści starą historię wysłanych maili',
)]
class EmailCleanupCommand extends Command
{
    public function __construct(
        private EmailService $emailService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('days', InputArgument::OPTIONAL, 'Liczba dni do przechowywania historii maili', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Pokaż co zostałoby usunięte bez rzeczywistego usuwania')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Wymuś usunięcie bez pytania o potwierdzenie')
            ->setHelp('
Ta komenda usuwa starą historię wysłanych maili.

Użycie:
  php bin/console app:email:cleanup              # Użyj domyślnych ustawień z konfiguracji
  php bin/console app:email:cleanup 30          # Usuń maile starsze niż 30 dni
  php bin/console app:email:cleanup --dry-run   # Pokaż co zostałoby usunięte
  php bin/console app:email:cleanup 7 --force   # Usuń bez pytania o potwierdzenie

Komenda może być uruchamiana przez cron job:
  0 2 * * * php /path/to/project/bin/console app:email:cleanup --force
            ');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $daysToKeep = $input->getArgument('days');
        $isDryRun = $input->getOption('dry-run');
        $isForced = $input->getOption('force');
        
        if ($daysToKeep !== null) {
            $daysToKeep = (int) $daysToKeep;
            if ($daysToKeep < 1) {
                $io->error('Liczba dni musi być większa od 0');
                return Command::FAILURE;
            }
        }

        $io->title('Czyszczenie historii maili');
        
        if ($daysToKeep) {
            $io->info("Usuwanie maili starszych niż {$daysToKeep} dni...");
        } else {
            $io->info('Używanie domyślnych ustawień z konfiguracji...');
        }

        if ($isDryRun) {
            $io->warning('TRYB TESTOWY - nie zostaną usunięte żadne dane');
            
            // W trybie dry-run nie implementujemy rzeczywistego sprawdzania
            // ponieważ wymagałoby to dodatkowej metody w repository
            $io->note('W rzeczywistym uruchomieniu zostałyby usunięte stare rekordy maili');
            return Command::SUCCESS;
        }

        if (!$isForced) {
            if (!$io->confirm('Czy na pewno chcesz usunąć starą historię maili?', false)) {
                $io->info('Operacja anulowana');
                return Command::SUCCESS;
            }
        }

        try {
            $deletedCount = $this->emailService->cleanupOldEmails($daysToKeep);
            
            if ($deletedCount > 0) {
                $io->success("Usunięto {$deletedCount} rekordów historii maili");
            } else {
                $io->info('Nie znaleziono starych rekordów do usunięcia');
            }
            
            // Pokaż statystyki po czyszczeniu
            $stats = $this->emailService->getEmailStatistics(30);
            $io->section('Statystyki (ostatnie 30 dni)');
            $io->table(
                ['Metryka', 'Wartość'],
                [
                    ['Łączna liczba maili', $stats['total']],
                    ['Wysłane pomyślnie', $stats['sent']],
                    ['Błędy wysyłania', $stats['failed']],
                    ['Unikalni odbiorcy', $stats['unique_recipients']],
                    ['Wskaźnik sukcesu', $stats['success_rate'] . '%']
                ]
            );
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Błąd podczas czyszczenia historii maili: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}