<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Types\DataTypeCollection;

class ClassBluePrint
{
    /** @var array<array{name: string, type: DataTypeCollection, default?: mixed}> */
    public array $constructorArguments = [];

    /** @var array<string, array{type: DataTypeCollection, default?: mixed}> */
    public array $properties = [];
}
