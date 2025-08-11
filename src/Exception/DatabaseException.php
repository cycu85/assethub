<?php

namespace App\Exception;

class DatabaseException extends BusinessLogicException
{
    public function __construct(
        string $message = 'Database operation failed',
        \Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($message, 500, $previous, $headers, $code);
    }
}