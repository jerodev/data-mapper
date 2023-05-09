<?php

namespace Jerodev\DataMapper\Types;

final class DataType
{
    /**
     * @param string $type
     * @param bool $isNullable
     * @param array<DataTypeCollection> $genericTypes
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $isNullable,
        public readonly array $genericTypes = [],
    ) {
    }

    public function isArray(): bool
    {
        return \in_array(
            $this->type,
            [
                'array',
                'iterable',
            ],
        );
    }
}
