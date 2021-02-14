<?php

namespace Jerodev\DataMapper\Models;

class DataType
{
    private string $type;
    private int $arrayLevel;
    private bool $isNullable;

    /**
     * @param string $type
     * @param int $arrayLevel
     * @param bool $isNullable
     */
    public function __construct(string $type, int $arrayLevel = 0, bool $isNullable = false)
    {
        $this->type = $type;
        $this->arrayLevel = $arrayLevel;
        $this->isNullable = $isNullable;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isArray(): bool
    {
        return $this->arrayLevel > 0;
    }

    public function isGenericArray(): bool
    {
        return \in_array($this->getType(), [
            'array',
            'iterable',
        ], true);
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isNativeType(): bool
    {
        return \in_array($this->getType(), [
            'array',
            'bool',
            'float',
            'int',
            'iterable',
            'object',
            'string',
        ], true);
    }

    public function getArrayChildType(): DataType
    {
        return new DataType(
            $this->getType(),
            $this->arrayLevel - 1,
            $this->isNullable(),
        );
    }

    public static function parse(string $type, bool $forceNullable = false): self
    {
        $arrayLevel = 0;
        if (\substr($type, -2) === '[]') {
            if (\preg_match('/^(.*?)((\[])+)$/', $type, $matches) === 1) {
                $type = $matches[1];
                $arrayLevel = \strlen($matches[2]) / 2;
            }
        }

        if (\substr($type, '0', 1) === '?') {
            $forceNullable = true;
            $type = \substr($type, 1);
        }

        return new self($type, $arrayLevel, $forceNullable);
    }
}
