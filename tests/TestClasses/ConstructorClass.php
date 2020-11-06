<?php

namespace Jerodev\DataMapper\Tests\TestClasses;

class ConstructorClass
{
    public string $foo;
    public string $bar;
    public string $baz;

    public function __construct(string $foo, string $bar = 'bar', string $baz = 'baz')
    {
        $this->foo = $foo;
        $this->bar = $bar;
        $this->baz = $baz;
    }
}