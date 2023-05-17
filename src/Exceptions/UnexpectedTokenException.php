<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class UnexpectedTokenException extends Exception
{
    public function __construct(string $token)
    {
        parent::__construct("Unexpected token '{$token}'");
    }
}
