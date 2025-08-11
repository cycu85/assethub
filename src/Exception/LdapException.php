<?php

namespace App\Exception;

class LdapException extends BusinessLogicException
{
    public function __construct(
        string $message = 'LDAP operation failed',
        \Throwable $previous = null,
        array $headers = [],
        int $code = 0
    ) {
        parent::__construct($message, 422, $previous, $headers, $code);
    }
}