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
     * @param array $data
     * @return object
     * @throws CouldNotResolveClassException
     */
    public function map(DataType $type, array $data): object
    {
        $class = $type->type;
        if (! \class_exists($class)) {
            $class = $this->classResolver->resolve($class);
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

        var_dump($blueprint);die();
    }
}
