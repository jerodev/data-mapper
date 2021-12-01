<?php

namespace Jerodev\DataMapper\Models;

class ClassBluePrint
{
    /**
     * The full classname for this object
     */
    private string $className;

    /**
     * Properties required for the constructor call.
     *
     * @var MethodParameter[]
     */
    private array $constructorProperties;

    /**
     * Indicating if the class has a static method `mapObject` to use for mapping.
     */
    private bool $mapsItself;

    /**
     * An optional function to be called after mapping the object.
     * Can be defined using the `PostMapping` attribute on the class.
     */
    private ?string $postMappingFunction;

    /**
     * Information about all public properties on the class.
     *
     * @var PropertyBluePrint[]
     */
    private array $properties;

    public function __construct(string $className)
    {
        $this->className = $className;
        $this->mapsItself = false;
        $this->constructorProperties = [];
        $this->postMappingFunction = null;
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

    public function getPostMappingFunction(): ?string
    {
        return $this->postMappingFunction;
    }

    public function setPostMappingFunction(?string $postMappingFunction): void
    {
        $this->postMappingFunction = $postMappingFunction;
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
