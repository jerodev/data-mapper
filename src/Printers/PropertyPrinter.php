<?php

namespace Jerodev\DataMapper\Printers;

use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\PropertyBluePrint;
use Jerodev\DataMapper\Printers\Property\PropertyTypeResolver;
use ReflectionProperty;

/**
 * This class takes a ReflectionProperty and tries to return all possible types this property supports.
 */
class PropertyPrinter
{
    private ClassNamePrinter $classNamePrinter;

    /** @var PropertyTypeResolver[] */
    private array $propertyTypeResolvers;

    public function __construct(ClassNamePrinter $classNamePrinter, ?array $propertyResolvers = null)
    {
        $this->classNamePrinter = $classNamePrinter;

        if ($propertyResolvers !== null) {
            $this->propertyTypeResolvers = $propertyResolvers;
            return;
        }

        $this->propertyTypeResolvers = [
            new Property\TypedPropertyResolver(),
            new Property\PhpDocPropertyResolver(),
        ];
    }

    public function print(ReflectionProperty $property): PropertyBluePrint
    {
        $bluePrint = new PropertyBluePrint($property->getName());

        $this->setPropertyTypes($property, $bluePrint);

        return $bluePrint;
    }

    private function setPropertyTypes(ReflectionProperty $property, PropertyBluePrint $bluePrint)
    {
        $types = $this->getPropertyTypes($property);

        foreach ($types as $type) {
            if ($type->isNativeType()) {
                continue;
            }

            $type->setType(
                $this->classNamePrinter->resolveClassName($type->getType(), $property->getDeclaringClass())
            );
        }

        $bluePrint->setTypes($types);
    }

    /**
     * @param ReflectionProperty $property
     * @return DataType[]
     */
    private function getPropertyTypes(ReflectionProperty $property): array
    {
        $genericArray = false;

        foreach ($this->propertyTypeResolvers as $typeResolver) {
            $types = $typeResolver->getPropertyType($property);
            if (empty($types)) {
                continue;
            }

            if (\count($types) === 1 && $types[0]->isGenericArray()) {
                $genericArray = true;
                continue;
            }

            return $types;
        }

        if ($genericArray) {
            return [new DataType('array')];
        }
    }
}
