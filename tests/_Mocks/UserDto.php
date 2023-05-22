<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

use Jerodev\DataMapper\Attributes\PostMapping;

#[PostMapping('post')]
class UserDto
{
    /** First name and last name */
    public string $name;

    /** @var array<self> */
    public array $friends = [];
    public ?SuitEnum $favoriteSuit = null;
    public ?int $score;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function post(): void
    {
        $this->name = \ucfirst($this->name);
    }
}
