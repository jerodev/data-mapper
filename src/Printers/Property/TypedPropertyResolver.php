<?php

namespace Jerodev\DataMapper\Printers\Property;

use Jerodev\DataMapper\Models\DataType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

class TypedPropertyResolver implements PropertyTypeResolver
{
    public function getPropertyType(ReflectionProperty $property): ?array
    {
        $reflectionType = $property->getType();
        if ($reflectionType === null) {
            return null;
        }

        if (PHP_MAJOR_VERSION >= 8 && $reflectionType instanceof ReflectionUnionType) {
            $propertyTypes = $this->getPhp8UnionTypes($reflectionType);
            if (! empty($propertyTypes)) {
                return $propertyTypes;
            }
        }

        if ($reflectionType instanceof ReflectionNamedType) {
            return [
                DataType::parse(
                    $reflectionType->getName(),
                    $reflectionType->allowsNull()
                ),
            ];
        }

        return null;
    }

    /** @return DataType[] */
    private function getPhp8UnionTypes(ReflectionUnionType $reflectionUnionType): ?array
    {
        $allowNull = ! empty(
            \array_filter($reflectionUnionType->getTypes(), static fn (ReflectionType $type) => $type->getName() === 'null')
        );

        $types = [];
        foreach ($reflectionUnionType->getTypes() as $reflectionType) {
            $typeString = $reflectionType->getName();
            if ($typeString === 'null') {
                continue;
            }

            $types[] = DataType::parse($reflectionType->getName(), $allowNull);
        }

        return $types;
    }
}
