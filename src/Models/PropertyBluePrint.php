<?php

namespace Jerodev\DataMapper\Models;

class PropertyBluePrint
{
    private string $propertyName;

    /** @var DataType[] */
    private array $types;

    private ?string $setter;

    public function __construct(string $propertyName)
    {
        $this->propertyName = $propertyName;
        $this->types = [];
        $this->setter = null;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function addType(DataType $type): void
    {
        $this->types[] = $type;
    }

    /** @return DataType[] */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function getSetter(): ?string
    {
        return $this->setter;
    }
}
