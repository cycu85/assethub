<?php

namespace App\Service;

use DateTime;
use DateTimeZone;

class DateTimeService
{
    public function __construct(
        private SettingService $settingService
    ) {
    }

    /**
     * Formatuje datę zgodnie z ustawieniami aplikacji
     */
    public function formatDate(DateTime|string $date = null): string
    {
        $date = $this->ensureDateTime($date);
        $format = $this->settingService->get('date_format', 'd/m/Y');
        
        return $this->applyTimezone($date)->format($format);
    }

    /**
     * Formatuje czas zgodnie z ustawieniami aplikacji
     */
    public function formatTime(DateTime|string $date = null): string
    {
        $date = $this->ensureDateTime($date);
        $format = $this->settingService->get('time_format', 'H:i');
        
        return $this->applyTimezone($date)->format($format);
    }

    /**
     * Formatuje datę i czas zgodnie z ustawieniami aplikacji
     */
    public function formatDateTime(DateTime|string $date = null): string
    {
        $date = $this->ensureDateTime($date);
        $dateFormat = $this->settingService->get('date_format', 'd/m/Y');
        $timeFormat = $this->settingService->get('time_format', 'H:i');
        
        return $this->applyTimezone($date)->format($dateFormat . ' ' . $timeFormat);
    }

    /**
     * Pobiera strefę czasową z ustawień
     */
    public function getTimezone(): DateTimeZone
    {
        $timezone = $this->settingService->get('timezone', 'Europe/Warsaw');
        return new DateTimeZone($timezone);
    }

    /**
     * Zwraca aktualną datę/czas w strefie czasowej aplikacji
     */
    public function now(): DateTime
    {
        return new DateTime('now', $this->getTimezone());
    }

    /**
     * Konwertuje string lub null na DateTime
     */
    private function ensureDateTime(DateTime|string $date = null): DateTime
    {
        if ($date === null) {
            return $this->now();
        }

        if (is_string($date)) {
            return new DateTime($date, $this->getTimezone());
        }

        return $date;
    }

    /**
     * Aplikuje strefę czasową do DateTime
     */
    private function applyTimezone(DateTime $date): DateTime
    {
        $cloned = clone $date;
        $cloned->setTimezone($this->getTimezone());
        return $cloned;
    }
}