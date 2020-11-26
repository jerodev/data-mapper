<?php

namespace Jerodev\DataMapper\Models;

class ClassBluePrint
{
    private string $className;

    /** @var MethodParameter[] */
    private array $constructorProperties;

    private bool $mapsItself;

    /** @var PropertyBluePrint[]  */
    private array $properties;

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->mapsItself = false;
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

    public function isMappingItself(): bool
    {
        return $this->mapsItself;
    }

    public function setMapsItself(bool $mapsItself = true): void
    {
        $this->mapsItself = $mapsItself;
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
