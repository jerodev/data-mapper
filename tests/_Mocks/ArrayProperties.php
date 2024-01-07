<?php

namespace Jerodev\DataMapper\Tests\_Mocks;

class ArrayProperties
{
    /** @var array<int> */
    public array $ints;

    /** @var array<int, array{foo: string, bar: int}> */
    public array $fieldsWithKeys;
}
