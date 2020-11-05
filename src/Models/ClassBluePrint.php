<?php

namespace Jerodev\DataMapper\Models;

class ClassBluePrint
{
    private string $className;

    /** @var PropertyBluePrint[]  */
    private array $properties;

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->properties = [];
    }

    public function addProperty(PropertyBluePrint $property): void
    {
        $this->properties[$property->getPropertyName()] = $property;
    }
}
