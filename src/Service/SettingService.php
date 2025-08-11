<?php

namespace App\Service;

use App\Repository\SettingRepository;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SettingService
{
    public function __construct(
        private SettingRepository $settingRepository,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
        private string $publicDir,
        private CacheInterface $settingsCache
    ) {
    }

    /**
     * Pobierz wartość ustawienia z cache
     */
    public function get(string $key, ?string $defaultValue = null): ?string
    {
        return $this->settingsCache->get('setting_' . $key, function (ItemInterface $item) use ($key, $defaultValue) {
            $item->expiresAfter(3600); // 1 hour
            return $this->settingRepository->getValueByKey($key, $defaultValue);
        });
    }

    /**
     * Ustaw wartość ustawienia i usuń z cache
     */
    public function set(string $key, ?string $value, string $category = 'general', string $type = 'text', ?string $description = null): void
    {
        $this->settingRepository->setValue($key, $value, $category, $type, $description);
        
        // Usuń z cache po aktualizacji
        $this->settingsCache->delete('setting_' . $key);
        
        $this->logger->info('Setting updated', [
            'key' => $key,
            'category' => $category,
            'type' => $type
        ]);
    }

    /**
     * Pobierz wszystkie ustawienia kategorii
     */
    public function getCategorySettings(string $category): array
    {
        return $this->settingRepository->getCategoryAsArray($category);
    }

    /**
     * Zapisz ustawienia ogólne
     */
    public function saveGeneralSettings(array $data, ?UploadedFile $logoFile = null): void
    {
        // Zapisz nazwę aplikacji
        if (isset($data['app_name'])) {
            $this->set('app_name', $data['app_name'], 'general', 'text', 'Nazwa aplikacji');
        }

        // Zapisz kolor główny
        if (isset($data['primary_color'])) {
            $this->set('primary_color', $data['primary_color'], 'general', 'color', 'Główny kolor aplikacji');
        }

        // Zapisz kolor tła sidebar
        if (isset($data['sidebar_bg_color'])) {
            $this->set('sidebar_bg_color', $data['sidebar_bg_color'], 'general', 'color', 'Kolor tła menu');
        }

        // Zapisz kolor tekstu sidebar
        if (isset($data['sidebar_text_color'])) {
            $this->set('sidebar_text_color', $data['sidebar_text_color'], 'general', 'color', 'Kolor tekstu w menu');
        }

        // Zapisz kolor aktywnego elementu sidebar
        if (isset($data['sidebar_active_color'])) {
            $this->set('sidebar_active_color', $data['sidebar_active_color'], 'general', 'color', 'Kolor aktywnego elementu w menu');
        }

        // Obsługa uploadu logo
        if ($logoFile) {
            $logoPath = $this->handleLogoUpload($logoFile);
            if ($logoPath) {
                $this->set('company_logo', $logoPath, 'general', 'file', 'Logo firmy');
            }
        }

        $this->logger->info('General settings saved', [
            'app_name' => $data['app_name'] ?? null,
            'primary_color' => $data['primary_color'] ?? null,
            'logo_uploaded' => $logoFile !== null
        ]);
    }

    /**
     * Obsługa uploadu logo firmy
     */
    private function handleLogoUpload(UploadedFile $file): ?string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = 'logo-' . $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        try {
            $uploadDir = $this->publicDir . '/uploads/logos';
            
            // Utwórz katalog jeśli nie istnieje
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Usuń stare logo jeśli istnieje
            $oldLogo = $this->get('company_logo');
            if ($oldLogo && file_exists($this->publicDir . $oldLogo)) {
                unlink($this->publicDir . $oldLogo);
            }

            $file->move($uploadDir, $fileName);
            
            return '/uploads/logos/' . $fileName;
        } catch (FileException $e) {
            $this->logger->error('Logo upload failed', [
                'error' => $e->getMessage(),
                'filename' => $fileName
            ]);
            return null;
        }
    }

    /**
     * Pobierz ustawienia ogólne z wartościami domyślnymi
     */
    public function getGeneralSettings(): array
    {
        return [
            'app_name' => $this->get('app_name', 'AssetHub'),
            'company_logo' => $this->get('company_logo', '/assets/images/logo-dark.png'),
            'primary_color' => $this->get('primary_color', '#405189'),
            'sidebar_bg_color' => $this->get('sidebar_bg_color', '#2a3042'),
            'sidebar_text_color' => $this->get('sidebar_text_color', '#ffffff'),
            'sidebar_active_color' => $this->get('sidebar_active_color', '#405189'),
        ];
    }

    /**
     * Zainicjalizuj domyślne ustawienia
     */
    public function initializeDefaultSettings(): void
    {
        $defaults = [
            'app_name' => ['value' => 'AssetHub', 'category' => 'general', 'type' => 'text', 'description' => 'Nazwa aplikacji'],
            'company_logo' => ['value' => '/assets/images/logo-dark.png', 'category' => 'general', 'type' => 'file', 'description' => 'Logo firmy'],
            'primary_color' => ['value' => '#405189', 'category' => 'general', 'type' => 'color', 'description' => 'Główny kolor aplikacji'],
        ];

        foreach ($defaults as $key => $config) {
            if (!$this->settingRepository->findByKey($key)) {
                $this->set($key, $config['value'], $config['category'], $config['type'], $config['description']);
            }
        }

        $this->logger->info('Default settings initialized');
    }
}