<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

use Jerodev\DataMapper\Tests\_Mocks\UserDto as GebruikerObject;

class Aliases
{
    /** @var array<string, GebruikerObject> */
    public array $userAliases;

    public SelfMapped $sm;
}
