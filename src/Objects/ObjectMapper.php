<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Types\DataType;

class ObjectMapper
{
    private readonly ClassResolver $classResolver;

    public function __construct()
    {
        $this->classResolver = new ClassResolver();
    }

    public function map(DataType $type, array $data): object
    {
        $class = $type->type;
        if (! \class_exists($class)) {
            $class = $this->classResolver->resolve($class);
        }

        // TODO: map object

        return new $class();
    }
}
