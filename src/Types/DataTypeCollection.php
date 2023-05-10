<?php

namespace Jerodev\DataMapper\Types;

class DataTypeCollection
{
    /** @var array<DataType> */
    public readonly array $types;

    /**
     * @param array<DataType> $types
     */
    public function __construct(array $types)
    {
        // Ensure null is always the last type
        $typesArray = [];
        $null = null;
        foreach ($types as $type) {
            if ($type->type === 'null') {
                $null = $type;
            } else {
                $typesArray[] = $type;
            }
        }

        if ($null !== null) {
            $typesArray[] = $null;
        }

        $this->types = $typesArray;
    }

    public function isNullable(): bool
    {
        return ! empty(
            \array_filter(
                $this->types,
                static fn (DataType $t) => $t->type === 'null',
            )
        );
    }

    public function __toString(): string
    {
        return \implode(
            '|',
            \array_map(static fn (DataType $type) => $type->__toString(), $this->types),
        );
    }
}
