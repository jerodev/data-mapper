<?php

namespace Jerodev\DataMapper\Models;

final class MapperOptions
{
    /**
     * If true, enums will be mapped using the `tryFrom` method instead of the `from` method.
     * This might result in null values being mapped to non-nullable fields.
     *
     * @var bool
     */
    public bool $enumTryFrom = false;

    /**
     * If true, mapping a null value to a non-nullable field will throw an UnexpectedNullValueException.
     *
     * @var bool
     */
    public bool $strictNullMapping = true;
}
