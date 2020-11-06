<?php

namespace Jerodev\DataMapper;

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

    public function map(string $className, array $data): object
    {
        $bluePrint = $this->bluePrinter->print($className);

        // TODO: check if class has constructor

        return $this->mapObjectProperties($bluePrint, $data);
    }

    /**
     * Map an object of a certain class.
     *
     * @param ClassBluePrint $bluePrint
     * @param array $data
     * @return object
     */
    private function mapObjectProperties(ClassBluePrint $bluePrint, array $data): object
    {
        $className = $bluePrint->getClassName();
        $object = new $className();

        foreach ($bluePrint->getProperties() as $property) {
            if (! \array_key_exists($property->getPropertyName(), $data)) {
                continue;
            }

            $reflectionProperty = new ReflectionProperty($className, $property->getPropertyName());
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

        return $object;
    }
}
