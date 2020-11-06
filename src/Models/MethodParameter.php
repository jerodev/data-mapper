<?php

namespace Jerodev\DataMapper\Models;

class MethodParameter
{
    private string $name;
    private ?DataType $type;
    private bool $required;
    private $defaultValue;

    public function __construct(string $name, ?DataType $type = null, bool $required = false, $defaultValue = null)
    {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): ?DataType
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /** @return mixed|null */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
