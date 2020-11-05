<?php

namespace Jerodev\DataMapper\Models;

class DataType
{
    private string $type;
    private bool $isArray;
    private bool $isNullable;

    /**
     * @param string $type
     * @param bool $isArray
     * @param bool $isNullable
     */
    public function __construct(string $type, bool $isArray = false, bool $isNullable = false)
    {
        $this->type = $type;
        $this->isArray = $isArray;
        $this->isNullable = $isNullable;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isArray(): bool
    {
        return $this->isArray;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isNativeType(): bool
    {
        return \in_array($this->type, [
            'array',
            'bool',
            'float',
            'int',
            'object',
            'string',
        ], true);
    }

    public static function parse(string $type, bool $forceNullable = false): self
    {
        $isArray = false;
        if (\substr($type, -2) === '[]') {
            $isArray = true;
            $type = \substr($type, 0, \strlen($type) - 2);
        }

        if (\substr($type, '0', 1) === '?') {
            $forceNullable = true;
            $type = \substr($type, 1);
        }

        return new self($type, $isArray, $forceNullable);
    }
}
