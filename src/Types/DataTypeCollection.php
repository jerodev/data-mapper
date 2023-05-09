<?php

namespace Jerodev\DataMapper\Types;

class DataTypeCollection
{
    public function __construct(
        public readonly array $types,
    ) {
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
}
