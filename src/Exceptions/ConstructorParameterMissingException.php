<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class ConstructorParameterMissingException extends Exception
{
    public function __construct(string $class, string $parameter, public readonly array $data)
    {
        parent::__construct("No value found for parameter `{$parameter}` for {$class}'s constructor.");
    }
}
