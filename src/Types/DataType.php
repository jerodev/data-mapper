<?php

namespace Jerodev\DataMapper\Types;

class DataType
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
            ],
        );
    }

    public function __toString(): string
    {
        $type = $this->type;

        if ($this->isNullable) {
            $type = '?' . $this->type;
        }

        if (! empty($this->genericTypes)) {
            $type .= '<' . \implode(', ', \array_map(static fn (DataTypeCollection $type) => $type->__toString(), $this->genericTypes)) . '>';
        }

        return $type;
    }
}
