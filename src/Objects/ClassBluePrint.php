<?php

namespace Jerodev\DataMapper\Objects;

class ClassBluePrint
{
    /** @var array<array{name: string, type: string, default?: mixed}> */
    public array $constructorArguments = [];

    /** @var array<string, string> An array with name as key and type as value */
    public array $properties = [];
}
