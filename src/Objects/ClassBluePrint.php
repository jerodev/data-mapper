<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Types\DataTypeCollection;

class ClassBluePrint
{
    /** @var array<array{name: string, type: DataTypeCollection, default?: mixed}> */
    public array $constructorArguments = [];

    /** @var array<string, DataTypeCollection> An array with name as key and type as value */
    public array $properties = [];
}
