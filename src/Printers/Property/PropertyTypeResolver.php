<?php

namespace Jerodev\DataMapper\Printers\Property;

use Jerodev\DataMapper\Models\DataType;
use ReflectionProperty;

interface PropertyTypeResolver
{
    /**
     * @param ReflectionProperty $property
     * @return DataType[]|null
     */
    public function getPropertyType(ReflectionProperty $property): ?array;
}
