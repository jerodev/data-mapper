<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

class UserDto
{
    /** First name and last name */
    public string $name;

    /** @var array<UserDto> */
    public array $friends = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
