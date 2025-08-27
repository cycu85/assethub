<?php

namespace App\Twig;

use App\Service\DateTimeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class DateTimeExtension extends AbstractExtension
{
    public function __construct(
        private DateTimeService $dateTimeService
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('app_date', [$this, 'formatDate']),
            new TwigFilter('app_time', [$this, 'formatTime']),
            new TwigFilter('app_datetime', [$this, 'formatDateTime']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_now', [$this, 'now']),
            new TwigFunction('app_now_formatted', [$this, 'nowFormatted']),
        ];
    }

    /**
     * Formatuje datę zgodnie z ustawieniami aplikacji
     */
    public function formatDate($date): string
    {
        return $this->dateTimeService->formatDate($date);
    }

    /**
     * Formatuje czas zgodnie z ustawieniami aplikacji
     */
    public function formatTime($date): string
    {
        return $this->dateTimeService->formatTime($date);
    }

    /**
     * Formatuje datę i czas zgodnie z ustawieniami aplikacji
     */
    public function formatDateTime($date): string
    {
        return $this->dateTimeService->formatDateTime($date);
    }

    /**
     * Zwraca aktualną datę/czas w strefie czasowej aplikacji
     */
    public function now(): \DateTime
    {
        return $this->dateTimeService->now();
    }

    /**
     * Zwraca sformatowaną aktualną datę/czas
     */
    public function nowFormatted(): string
    {
        return $this->dateTimeService->formatDateTime();
    }
}