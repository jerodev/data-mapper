<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\ConstructorParameterMissingException;
use Jerodev\DataMapper\Models\ClassBluePrint;
use ReflectionProperty;
use Throwable;

class ObjectMapper
{
    private BluePrinter $bluePrinter;
    private Mapper $mapper;

    public function __construct(Mapper $mapper, BluePrinter $bluePrinter)
    {
        $this->bluePrinter = $bluePrinter;
        $this->mapper = $mapper;
    }

    public function map(string $className, $data): object
    {
        $bluePrint = $this->bluePrinter->print($className);
        $className = $bluePrint->getClassName();

        // If the class implements MapsItself, just create the object using the mapObject function
        if ($bluePrint->isMappingItself()) {
            return \call_user_func([$className, 'mapObject'], $data);
        }

        // If the class has a constructor, try passing the required parameters
        if (! empty($bluePrint->getConstructorProperties())) {
            $object = $this->createObjectUsingConstructor($bluePrint, (array) $data);
        } else {
            $object = new $className();
        }

        if (\is_array($data)) {
            $this->mapObjectProperties($object, $bluePrint, $data);
        }

        return $object;
    }

    private function mapObjectProperties(object $object, ClassBluePrint $bluePrint, array $data): void
    {
        foreach ($bluePrint->getProperties() as $property) {
            if (! \array_key_exists($property->getPropertyName(), $data)) {
                continue;
            }

            $reflectionProperty = new ReflectionProperty($bluePrint->getClassName(), $property->getPropertyName());
            foreach ($property->getTypes() as $type) {
                try {
                    $object->{$property->getPropertyName()} = $this->mapper->map($type, $data[$property->getPropertyName()]);
                } catch (Throwable $e) {
                    continue;
                }

                // If the property has a value, we're good to go.
                if ($reflectionProperty->isInitialized($object) && $object->{$property->getPropertyName()} !== null) {
                    break;
                }
            }
        }
    }

    private function createObjectUsingConstructor(ClassBluePrint $bluePrint, array $data): object
    {
        $constructorValues = [];

        foreach ($bluePrint->getConstructorProperties() as $constructorProperty) {
            if (\array_key_exists($constructorProperty->getName(), $data)) {
                $constructorValues[] = $this->mapper->map($constructorProperty->getType(), $data[$constructorProperty->getName()]);
            } else if (! $constructorProperty->isRequired()) {
                $constructorValues[] = $constructorProperty->getDefaultValue();
            } else {
                throw new ConstructorParameterMissingException($bluePrint->getClassName(), $constructorProperty->getName());
            }
        }

        $className = $bluePrint->getClassName();
        return new $className(...$constructorValues);
    }
}
