<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

class Nullable
{
    public ?string $name;

    public function __construct(
        public ?int $id,
    ) {
    }
}
