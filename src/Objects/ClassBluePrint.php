<?php

namespace Jerodev\DataMapper\Objects;

use Attribute;
use Jerodev\DataMapper\Types\DataTypeCollection;

class ClassBluePrint
{
    public bool $mapsItself = false;

    /** @var array<array{name: string, type: DataTypeCollection, default?: mixed}> */
    public array $constructorArguments = [];

    /** @var array<string, array{type: DataTypeCollection, default?: mixed}> */
    public array $properties = [];

    /** @var array<Attribute> */
    public array $classAttributes = [];

    public function __construct(
        public readonly string $namespacedClassName,
        public readonly string $fileName,
    ) {
    }
}
