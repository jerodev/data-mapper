<?php

namespace Jerodev\DataMapper\Printers\Property;

use ReflectionProperty;

class PhpDocPropertyResolver implements PropertyTypeResolver
{
    public function getPropertyType(ReflectionProperty $property): ?array
    {
    }
}
