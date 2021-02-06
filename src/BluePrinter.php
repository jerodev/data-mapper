<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\ClassNotFoundException;
use Jerodev\DataMapper\Models\ClassBluePrint;
use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\MethodParameter;
use Jerodev\DataMapper\Models\PropertyBluePrint;
use ReflectionClass;
use ReflectionProperty;
use ReflectionUnionType;

class BluePrinter
{
    /** @var ClassBluePrint[] */
    private array $bluePrintCache;

    /** @var string[][] */
    private array $importCache;

    public function __construct()
    {
        $this->bluePrintCache = [];
        $this->importCache = [];
    }

    /**
     * @param object|string $source
     * @param string|null $parentClass
     * @return ClassBluePrint
     * @throws ClassNotFoundException
     */
    public function print($source): ClassBluePrint
    {
        if (\is_object($source)) {
            $source = \get_class($source);
        }

        $cacheSlug = "{$source}";
        if (\array_key_exists($cacheSlug, $this->bluePrintCache)) {
            return $this->bluePrintCache[$cacheSlug];
        }

        $fqcn = $this->getFullyQualifiedClassName($source);
        $bluePrint = new ClassBluePrint($fqcn);

        $reflection = new ReflectionClass($fqcn);
        if (\in_array(MapsItself::class, \class_implements($source), true)) {
            $bluePrint->setMapsItself();
        } else {
            // Map properties
            foreach ($reflection->getProperties() as $property) {
                $bluePrint->addProperty(
                    $this->printProperty($property)
                );
            }

            // Map constructor
            $constructor = $reflection->getConstructor();
            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $parameter) {
                    $dataType = null;
                    if ($parameter->getType()) {
                        $dataType = DataType::parse((string) $parameter->getType());
                    }
                    if ($dataType === null || $dataType->isGenericArray()) {
                        $property = $bluePrint->getProperty($parameter->getName());

                        if ($property !== null && ! empty($property->getTypes())) {
                            $dataType = $bluePrint->getProperty($parameter->getName())->getTypes()[0];
                        }
                    }

                    $bluePrint->addConstructorProperty(
                        new MethodParameter(
                            $parameter->getName(),
                            $dataType,
                            ! $parameter->isDefaultValueAvailable(),
                            $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                        )
                    );
                }
            }
        }

        $this->bluePrintCache[$cacheSlug] = $bluePrint;

        return $bluePrint;
    }

    private function getFullyQualifiedClassName(string $source, ?ReflectionClass $declaringClass = null): string
    {
        // Does the source start at root?
        if (\substr($source, 0, 1) === '\\') {
            if (\class_exists($source)) {
                return $source;
            } else {
                throw new ClassNotFoundException($source);
            }
        }

        // Can we find the source starting from the root?
        if (\class_exists('\\' . $source)) {
            return '\\' . $source;
        }

        if ($declaringClass !== null) {
            // Can we find the source in the parent namespace?
            $withParentNamespace = '\\' . \ltrim($declaringClass->getNamespaceName(), '\\') . '\\' . $source;
            if (\class_exists($withParentNamespace)) {
                return $withParentNamespace;
            }

            // Try finding the source class in parent imports.
            $imports = $this->getClassImports($declaringClass);
            if (\array_key_exists($source, $imports) && \class_exists($imports[$source])) {
                return $imports[$source];
            }

            // Try the parent of the parent class.
            $parentParent = $declaringClass->getParentClass();
            if ($parentParent instanceof ReflectionClass && $parentParent->isUserDefined()) {
                return $this->getFullyQualifiedClassName($source, $parentParent);
            }
        }

        throw new ClassNotFoundException($source);
    }

    private function getClassImports(ReflectionClass $class): array
    {
        if (\array_key_exists($class->getName(), $this->importCache)) {
            return $this->importCache[$class->getName()];
        }

        $resource = \fopen($class->getFileName(), 'r');
        $imports = [];

        while ($line = \fgets($resource)) {
            // Lines starting with 'use ' indicate an import statement.
            if (\substr($line, 0, 4) === 'use ') {
                $parts = \explode(' ', \trim($line));

                // Skip imports for functions or constants.
                if ($parts[1] === 'function' || $parts[1] === 'const') {
                    continue;
                }

                $name = $parts[3] ?? null;
                if ($name === null) {
                    $fqcnParts = \explode('\\', $parts[1]);
                    $name = \end($fqcnParts);
                }

                $imports[$name] = $parts[1];
            }

            // When the class starts, no more imports are available.
            if (\substr($line, 0, 6) === 'class ' || \substr($line, 0, 12) === 'final class ') {
                break;
            }
        }

        $this->importCache[$class->getName()] = $imports;

        return $imports;
    }

    private function printProperty(ReflectionProperty $property): PropertyBluePrint
    {
        $bluePrint = new PropertyBluePrint($property->getName());
        $genericArray = false;

        // Test for PHP7.4 typed properties.
        if ($type = $property->getType()) {

            // PHP8.0 union types
            if ($type instanceof ReflectionUnionType) {
                $allowNull = false;
                foreach ($type->getTypes() as $reflectionType) {
                    $typeString = $reflectionType->getName();

                    if ($typeString === 'null') {
                        $allowNull = true;
                        continue;
                    }

                    $dataType = DataType::parse($reflectionType->getName(), $allowNull);
                    $bluePrint->addType($dataType);
                }

                return $bluePrint;
            }

            $dataType = DataType::parse($type);

            // If the type is not a generic array, roll with it!
            if ($dataType->isGenericArray()) {
                $genericArray = true;
            } else {
                $bluePrint->addType($dataType);
                return $bluePrint;
            }
        }

        // Get the @var annotation from the docblock if any.
        if ($docBlock = $property->getDocComment()) {
            if (\preg_match('/@var\s+((\|?\??[\w\d]+(?:\[])?)+)/', $docBlock, $matches) === 1 && \count($matches) > 1) {
                $types = \explode('|', $matches[1]);
                foreach ($types as $type) {
                    $type = \trim($type);

                    if ($type === 'array') {
                        $genericArray = true;
                        continue;
                    }

                    if ($type === 'null') {
                        continue;
                    }

                    $dataType = DataType::parse($type, isset($dataType) && $dataType->isNullable());

                    // If datatype is not native, make sure we have the full namespace for the class
                    if (! $dataType->isNativeType()) {
                        if (! \class_exists($dataType->getType())) {
                            $dataType->setType($this->getFullyQualifiedClassName($dataType->getType(), $property->getDeclaringClass()));
                        }
                    }

                    $bluePrint->addType($dataType);
                }

                if (! empty($bluePrint->getTypes())) {
                    return $bluePrint;
                }
            }
        }

        if ($genericArray) {
            $bluePrint->addType(new DataType('array'));
        }

        return $bluePrint;
    }
}
