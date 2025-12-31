<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

final class MixedParam
{
    public function __construct(
        public string $key,
        public mixed $data,
    ) {
    }
}
