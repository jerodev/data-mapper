<?php

namespace Jerodev\DataMapper\Models;

class ClassBluePrint
{
    private string $className;

    /** @var MethodParameter[] */
    private array $constructorProperties;

    private bool $mapsFromArray;

    /** @var PropertyBluePrint[]  */
    private array $properties;

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->mapsFromArray = false;
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

    public function mapsFromArray(): bool
    {
        return $this->mapsFromArray;
    }

    public function setMapsFromArray(bool $mapsFromArray = true): void
    {
        $this->mapsFromArray = $mapsFromArray;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name): ?PropertyBluePrint
    {
        return $this->properties[$name] ?? null;
    }

    public function addProperty(PropertyBluePrint $property): void
    {
        $this->properties[$property->getPropertyName()] = $property;
    }
}
