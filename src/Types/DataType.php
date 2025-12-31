<?php

namespace Jerodev\DataMapper\Types;

class DataType
{
    /**
     * @param string $type
     * @param bool $isNullable
     * @param array<DataTypeCollection> $genericTypes
     * @param string|null $arrayFields
     */
    public function __construct(
        public readonly string $type,
        public readonly bool $isNullable,
        public readonly array $genericTypes = [],
        public readonly ?string $arrayFields = null,
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

    public function isGenericArray(): bool
    {
        return $this->isArray() && empty($this->genericTypes);
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
                'mixed',
            ],
        );
    }

    /**
     * returns a new instance of itself that is not nullable
     */
    public function removeNullable(): self
    {
        return new self($this->type, false, $this->genericTypes);
    }
}
