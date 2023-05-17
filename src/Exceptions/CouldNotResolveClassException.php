<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class CouldNotResolveClassException extends Exception
{
    public function __construct(string $class, ?string $filename = null)
    {
        $message = "Could not resolve class \'{$class}\'";
        if ($filename !== null) {
            $message .= " in file \'{$filename}\'";
        }

        parent::__construct($message . '.');
    }
}
