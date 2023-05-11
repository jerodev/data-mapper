<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class UnexpectedNullValueException extends Exception
{
    public function __construct(string $propertyName)
    {
        parent::__construct("Unexpected null value for property '{$propertyName}'");
    }
}
