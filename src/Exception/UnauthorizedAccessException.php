<?php

namespace App\Exception;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UnauthorizedAccessException extends HttpException
{
    public function __construct(
        string $message = 'Access denied',
        \Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct(Response::HTTP_FORBIDDEN, $message, $previous, $headers, $code);
    }
}