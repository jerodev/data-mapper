<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\ClassNotFoundException;
use Jerodev\DataMapper\Models\ClassBluePrint;
use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\PropertyBluePrint;
use ReflectionClass;
use ReflectionProperty;

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
    public function print($source, ?string $parentClass = null): ClassBluePrint
    {
        if (\is_object($source)) {
            $source = \get_class($source);
        }

        $cacheSlug = "{$parentClass}.{$source}";
        if (\array_key_exists($cacheSlug, $this->bluePrintCache)) {
            return $this->bluePrintCache[$cacheSlug];
        }

        $fqcn = $this->getFullyQualifiedClassName($source, $parentClass);
        $bluePrint = new ClassBluePrint($fqcn);

        $reflection = new ReflectionClass($fqcn);
        foreach ($reflection->getProperties() as $property) {
            $bluePrint->addProperty(
                $this->printProperty($property)
            );
        }

        $this->bluePrintCache[$cacheSlug] = $bluePrint;

        return $bluePrint;
    }

    private function getFullyQualifiedClassName(string $source, ?string $parentClass = null): string
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

        if ($parentClass !== null && \class_exists($parentClass)) {
            $parentReflection = new ReflectionClass($parentClass);

            // Can we find the source in the parent namespace?
            $withParentNamespace = '\\' . \ltrim($parentReflection->getNamespaceName(), '\\') . '\\' . $source;
            if (\class_exists($withParentNamespace)) {
                return $withParentNamespace;
            }

            // Try finding the source class in parent imports.
            $imports = $this->getClassImports($parentReflection);
            if (\array_key_exists($source, $imports) && \class_exists($imports[$source])) {
                return $imports[$source];
            }

            // Try the parent of the parent class.
            $parentParent = $parentReflection->getParentClass();
            if ($parentParent instanceof ReflectionClass && $parentParent->isUserDefined()) {
                return $this->getFullyQualifiedClassName($source, $parentParent->getName());
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
            $dataType = DataType::parse($type);

            // If the type is not a generic array, roll with it!
            if ($dataType->getType() !== 'array') {
                $bluePrint->addType($dataType);
                return $bluePrint;
            } else {
                $genericArray = true;
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

                    $bluePrint->addType(DataType::parse($type, isset($dataType) && $dataType->isNullable()));
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
