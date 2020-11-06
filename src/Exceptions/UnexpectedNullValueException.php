<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class UnexpectedNullValueException extends Exception
{
    public function __construct()
    {
        parent::__construct('Unexpected null value for non nullable type.');
    }
}
