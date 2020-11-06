<?php

namespace Jerodev\DataMapper\Tests\TestClasses;

class RecursiveClass
{
    public string $value;
    public ?RecursiveClass $recursive;
}
