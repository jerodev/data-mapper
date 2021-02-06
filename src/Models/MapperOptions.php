<?php

namespace Jerodev\DataMapper\Models;

final class MapperOptions
{
    public function __construct(
        bool $strictNullMapping = true
    ) {
        $this->strictNullMapping = $strictNullMapping;
    }

    public bool $strictNullMapping;
}
