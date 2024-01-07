<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class InvalidTypeException extends Exception
{
    public function __construct(string $type, ?string $message = null)
    {
        if ($message) {
            parent::__construct("Invalid type '{$type}': {$message}");
        } else {
            parent::__construct("Invalid type '{$type}'");
        }
    }
}
