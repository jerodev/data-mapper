<?php

namespace Jerodev\DataMapper\Printers\Property;

use Jerodev\DataMapper\Models\DataType;
use ReflectionProperty;

class PhpDocPropertyResolver implements PropertyTypeResolver
{
    public function getPropertyType(ReflectionProperty $property): ?array
    {
        $doc = $property->getDocComment();
        if (! \is_string($doc)) {
            return null;
        }

        $rawTypes = $this->getVarPhpDocString($doc);
        if ($rawTypes === null) {
            return null;
        }

        $allowNull = \in_array('null', $rawTypes, true);
        $types = [];
        foreach ($rawTypes as $rawType) {
            if ($rawType === 'null') {
                continue;
            }

            $types[] = DataType::parse($rawType, $allowNull);
        }

        return $types;
    }

    private function getVarPhpDocString(string $docBlock): ?array
    {
        if (\preg_match('/@var\s+([^\n*]+)/', $docBlock, $matches) === 1 && \count($matches) > 1) {
            return \preg_split('/\s*\|\s*/', $matches[1]);
        }

        return null;
    }
}
