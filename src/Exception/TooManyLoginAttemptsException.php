<?php

namespace App\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TooManyLoginAttemptsException extends AuthenticationException
{
    public function getMessageKey(): string
    {
        return 'Too many login attempts. Please try again later.';
    }
}
