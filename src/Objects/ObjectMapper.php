<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\Types\DataType;

class ObjectMapper
{
    private readonly ClassBluePrinter $classBluePrinter;
    private readonly ClassResolver $classResolver;

    public function __construct(
        private readonly Mapper $mapper,
    ) {
        $this->classBluePrinter = new ClassBluePrinter();
        $this->classResolver = new ClassResolver();
    }

    /**
     * @param DataType $type
     * @param array|string $data
     * @return object
     * @throws CouldNotResolveClassException
     */
    public function map(DataType $type, array|string $data): object
    {
        $class = $this->classResolver->resolve($type->type);

        // If the data is a string and the class is an enum, create the enum.
        if (\is_string($data) && \is_subclass_of($class, \BackedEnum::class)) {
            return $class::from($data);
        }

        $mapFileName = 'mapper_' . \md5($class);
        if (! \file_exists($mapFileName . '.php')) {
            \file_put_contents($mapFileName . '.php', $this->createObjectMappingFunction($class));
        }

        // Include the function containing file and call the function.
        require_once($mapFileName . '.php');
        return ($mapFileName)($this->mapper, $data);
    }

    private function createObjectMappingFunction(string $class): string
    {
        $blueprint = $this->classBluePrinter->print($class);

//        var_dump($blueprint);die();
    }
}
