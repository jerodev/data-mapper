<?php

namespace Jerodev\DataMapper\Exceptions;

use Exception;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;

class CouldNotMapValueException extends Exception
{
    public function __construct(
        public readonly mixed $data,
        public readonly DataType|DataTypeCollection $type,
    ) {
        parent::__construct('Could not map data');
    }
}
