<?php

namespace Jerodev\DataMapper\Printers;

use Jerodev\DataMapper\Attributes\PostMapping;
use Jerodev\DataMapper\Exceptions\ClassNotFoundException;
use Jerodev\DataMapper\MapsItself;
use Jerodev\DataMapper\Models\ClassBluePrint;
use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\MethodParameter;
use ReflectionClass;
use ReflectionException;

class BluePrinter
{
    private ClassNamePrinter $classNamePrinter;
    private PropertyPrinter $propertyPrinter;

    /** @var ClassBluePrint[] */
    private array $bluePrintCache;

    public function __construct(?ClassNamePrinter $classNamePrinter = null, ?PropertyPrinter $propertyPrinter = null)
    {
        $this->classNamePrinter = $classNamePrinter ?? new ClassNamePrinter();
        $this->propertyPrinter = $propertyPrinter ?? new PropertyPrinter($this->classNamePrinter);

        $this->bluePrintCache = [];
    }

    /**
     * @param object|string $source
     * @return ClassBluePrint
     * @throws ClassNotFoundException|ReflectionException
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

        $fqcn = $this->classNamePrinter->resolveClassName($source);
        if ($fqcn === null) {
            throw new ClassNotFoundException($source);
        }

        $bluePrint = new ClassBluePrint($fqcn);

        $reflection = new ReflectionClass($fqcn);
        if (\in_array(MapsItself::class, \class_implements($source), true)) {
            $bluePrint->setMapsItself();
        } else {
            // Map properties
            foreach ($reflection->getProperties() as $property) {
                $bluePrint->addProperty($this->propertyPrinter->print($property));
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

        $this->findPostMappingCallbacks($reflection, $bluePrint);

        $this->bluePrintCache[$cacheSlug] = $bluePrint;

        return $bluePrint;
    }

    private function findPostMappingCallbacks(ReflectionClass $reflection, ClassBluePrint $bluePrint): void
    {
        if (PHP_MAJOR_VERSION < 8 || ! $reflection->isUserDefined()) {
            return;
        }

        if (\method_exists($reflection, 'getAttributes')) {
            $postMappingAttributes = $reflection->getAttributes(PostMapping::class);

            if (! empty($postMappingAttributes)) {
                $arguments = $postMappingAttributes[0]->getArguments();
                $bluePrint->setPostMapping(\reset($arguments));
                return;
            }

            if ($reflection->getParentClass() && $reflection->getParentClass()->isUserDefined()) {
                $this->findPostMappingCallbacks($reflection->getParentClass(), $bluePrint);
            }
        }
    }
}
