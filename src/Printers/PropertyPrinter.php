<?php

namespace Jerodev\DataMapper\Printers;

use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\PropertyBluePrint;
use Jerodev\DataMapper\Printers\Property\PropertyTypeResolver;
use ReflectionProperty;

class PropertyPrinter
{
    /** @var PropertyTypeResolver[] */
    private array $propertyTypeResolvers;

    public function __construct(?array $propertyResolvers = null)
    {
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

    private function setPropertyTypes(ReflectionProperty $property, PropertyBluePrint $bluePrint): void
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

            $bluePrint->setTypes($types);
            break;
        }

        if ($genericArray) {
            $bluePrint->setTypes([new DataType('array')]);
        }
    }
}