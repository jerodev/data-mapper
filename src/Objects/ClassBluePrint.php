<?php

namespace Jerodev\DataMapper\Objects;

class ClassBluePrint
{
    /** @var array<array{name: string, type: string, default?: mixed}> */
    public array $constructorArguments;

    /** @var array<array{name: string, type: string}> */
    public array $properties;
}
