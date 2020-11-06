<?php

namespace Jerodev\DataMapper\Models;

class ClassBluePrint
{
    private string $className;

    /** @var MethodParameter[] */
    private array $constructorProperties;

    /** @var PropertyBluePrint[]  */
    private array $properties;

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->constructorProperties = [];
        $this->properties = [];
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getConstructorProperties(): array
    {
        return $this->constructorProperties;
    }

    public function addConstructorProperty(MethodParameter $parameter): void
    {
        $this->constructorProperties[] = $parameter;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function addProperty(PropertyBluePrint $property): void
    {
        $this->properties[$property->getPropertyName()] = $property;
    }
}
