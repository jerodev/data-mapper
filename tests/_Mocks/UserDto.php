<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

class UserDto
{
    /** First name and last name */
    public string $name;

    /** @var array<self> */
    public array $friends = [];
    public ?SuitEnum $favoriteSuit = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
