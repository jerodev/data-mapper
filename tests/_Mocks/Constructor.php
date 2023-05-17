<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

final class Constructor
{
    /**
     * @param UserDto[] $users
     */
    public function __construct(
        public readonly array $users,
    ) {
    }
}
