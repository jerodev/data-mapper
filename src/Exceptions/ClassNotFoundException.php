<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;

class ClassNotFoundException extends Exception
{
    public function __construct(string $className)
    {
        parent::__construct("Class `{$className}` could not be found");
    }
}