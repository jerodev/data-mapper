<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Attributes\PostMapping;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;
use ReflectionClass;

class ClassBluePrinter
{
    private readonly DocBlockParser $docBlockParser;
    private readonly DataTypeFactory $dataTypeFactory;

    public function __construct()
    {
        $this->dataTypeFactory = new DataTypeFactory();
        $this->docBlockParser = new DocBlockParser();
    }

    public function print(string $class): ClassBluePrint
    {
        $reflection = new ReflectionClass($class);
        $blueprint = new ClassBluePrint($class, $reflection->getFileName());

        $this->printConstructor($reflection, $blueprint);
        $this->printProperties($reflection, $blueprint);
        $this->printAttributes($reflection, $blueprint);

        return $blueprint;
    }

    private function printConstructor(ReflectionClass $reflection, ClassBluePrint $bluePrint): void
    {
        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return;
        }

        // Get information from potential doc comment
        $doc = null;
        if ($constructor->getDocComment()) {
            $doc = $this->docBlockParser->parse($constructor->getDocComment());
        }

        // Loop over each parameter and describe it
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType()?->getName();
            if ($doc !== null && \in_array($type, [null, 'array', 'iterable'])) {
                $type = $doc->getParamType($param->getName());
            }

            $arg = [
                'type' => $this->resolveType(
                    $this->dataTypeFactory->fromString($type),
                    $reflection->getName(),
                ),
            ];
            if ($param->isDefaultValueAvailable()) {
                $arg['default'] = $param->getDefaultValue();
            }

            $bluePrint->constructorArguments[$param->getName()] = $arg;
        }
    }

    private function printProperties(ReflectionClass $reflection, ClassBluePrint $blueprint): void
    {
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            if (! $property->isPublic() || $property->isReadOnly()) {
                continue;
            }

            // Already mapped through constructor?
            if (\array_key_exists($property->getName(), $blueprint->constructorArguments)) {
                continue;
            }

            $type = $property->getType()?->getName();
            if (\in_array($type, [null, 'array', 'iterable']) && $property->getDocComment()) {
                $doc = $this->docBlockParser->parse($property->getDocComment());
                if ($doc->varType !== null) {
                    $type = $doc->varType;
                }
            }

            $mapped = [
                'type' => $this->resolveType(
                    $this->dataTypeFactory->fromString($type, $property->getType()->allowsNull()),
                    $reflection->getName(),
                ),
            ];
            if ($property->hasDefaultValue()) {
                $mapped['default'] = $property->getDefaultValue();
            }

            $blueprint->properties[$property->getName()] = $mapped;
        }
    }

    private function printAttributes(ReflectionClass $reflection, ClassBluePrint $blueprint): void
    {
        foreach ($reflection->getAttributes(PostMapping::class) as $attribute) {
            $blueprint->classAttributes[] = $attribute->newInstance();
        }

        // Also check parent for relevant attributes
        if ($reflection->getParentClass()) {
            $this->printAttributes($reflection->getParentClass(), $blueprint);
        }
    }

    private function resolveType(DataTypeCollection $type, string $className): DataTypeCollection
    {
        $baseClassName = \explode('\\', $className);
        $baseClassName = \end($baseClassName);

        $collection = [];
        foreach ($type->types as $dataType) {
            $generics = [];
            foreach ($dataType->genericTypes as $genericType) {
                $generics[] = $this->resolveType($genericType, $className);
            }

            $typeName = $dataType->type;
            if (\in_array($typeName, ['self', 'static', $baseClassName])) {
                $typeName = $className;
            }

            $collection[] = new DataType(
                $typeName,
                $dataType->isNullable,
                $generics,
            );
        }

        return new DataTypeCollection($collection);
    }
}
