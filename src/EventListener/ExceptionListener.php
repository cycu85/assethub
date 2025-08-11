<?php

namespace App\EventListener;

use App\Exception\BusinessLogicException;
use App\Exception\UnauthorizedAccessException;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;

class ExceptionListener
{
    public function __construct(
        private LoggerInterface $logger,
        private Environment $twig,
        private string $environment = 'dev'
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Logowanie wyjątku
        $this->logException($exception, $request);

        // Tworzenie odpowiedzi w zależności od typu wyjątku
        $response = $this->createErrorResponse($exception, $request);
        
        $event->setResponse($response);
    }

    private function logException(\Throwable $exception, Request $request): void
    {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'url' => $request->getUri(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'trace' => $this->environment === 'dev' ? $exception->getTraceAsString() : 'hidden'
        ];

        // Dodaj informacje o użytkowniku jeśli dostępne
        if ($request->hasSession() && $request->getSession()->has('_security_main')) {
            $context['user'] = 'authenticated';
        }

        // Loguj w zależności od typu wyjątku
        if ($exception instanceof UnauthorizedAccessException) {
            $this->logger->warning('Unauthorized access attempt', $context);
        } elseif ($exception instanceof ValidationException) {
            $context['violations'] = $exception->getViolations();
            $this->logger->info('Validation failed', $context);
        } elseif ($exception instanceof BusinessLogicException) {
            $this->logger->error('Business logic error', $context);
        } elseif ($exception instanceof HttpException && $exception->getStatusCode() < 500) {
            $this->logger->warning('Client error', $context);
        } else {
            $this->logger->critical('Server error', $context);
        }
    }

    private function createErrorResponse(\Throwable $exception, Request $request): Response
    {
        $statusCode = $this->getStatusCode($exception);
        $message = $this->getErrorMessage($exception);

        // Sprawdź czy to request API (JSON)
        if ($this->isApiRequest($request)) {
            return $this->createJsonErrorResponse($exception, $statusCode, $message);
        }

        // Dla zwykłych requestów zwróć stronę błędu
        return $this->createHtmlErrorResponse($statusCode, $message);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getErrorMessage(\Throwable $exception): string
    {
        if ($this->environment === 'prod') {
            return match (true) {
                $exception instanceof UnauthorizedAccessException => 'Brak dostępu do zasobu',
                $exception instanceof ValidationException => 'Nieprawidłowe dane wejściowe',
                $exception instanceof BusinessLogicException => 'Wystąpił błąd podczas przetwarzania',
                $exception instanceof HttpException && $exception->getStatusCode() === 404 => 'Strona nie została znaleziona',
                default => 'Wystąpił nieoczekiwany błąd'
            };
        }

        return $exception->getMessage();
    }

    private function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->getPathInfo(), '/api/') ||
               $request->getContentTypeFormat() === 'json' ||
               in_array('application/json', $request->getAcceptableContentTypes());
    }

    private function createJsonErrorResponse(\Throwable $exception, int $statusCode, string $message): JsonResponse
    {
        $data = [
            'error' => true,
            'message' => $message,
            'status' => $statusCode
        ];

        // Dodaj szczegóły dla ValidationException
        if ($exception instanceof ValidationException) {
            $data['violations'] = $exception->getViolations();
        }

        // Dodaj szczegóły w trybie dev
        if ($this->environment === 'dev') {
            $data['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ];
        }

        return new JsonResponse($data, $statusCode);
    }

    private function createHtmlErrorResponse(int $statusCode, string $message): Response
    {
        try {
            $template = match ($statusCode) {
                403 => 'error/403.html.twig',
                404 => 'error/404.html.twig', 
                500 => 'error/500.html.twig',
                default => 'error/default.html.twig'
            };

            $content = $this->twig->render($template, [
                'status_code' => $statusCode,
                'message' => $message,
                'environment' => $this->environment
            ]);

            return new Response($content, $statusCode);
        } catch (\Exception $e) {
            // Fallback jeśli template nie istnieje
            return new Response(
                sprintf('<h1>Błąd %d</h1><p>%s</p>', $statusCode, htmlspecialchars($message)),
                $statusCode
            );
        }
    }
}