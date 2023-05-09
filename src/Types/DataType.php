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
        return ! empty($this->genericTypes)
            && \in_array(
                $this->type,
                [
                    'array',
                    'iterable',
                ],
            );
    }

    /** @see https://www.php.net/manual/en/language.types.intro.php */
    public function isNative(): bool
    {
        return \in_array(
            $this->type,
            [
                'null',
                'bool',
                'float',
                'int',
                'string',
                'object',
            ],
        );
    }
}
