<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends BusinessLogicException
{
    private array $violations = [];

    public function __construct(
        string $message = 'Validation failed',
        array $violations = [],
        \Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        $this->violations = $violations;
        parent::__construct($message, Response::HTTP_BAD_REQUEST, $previous, $headers, $code);
    }

    public function getViolations(): array
    {
        return $this->violations;
    }
}