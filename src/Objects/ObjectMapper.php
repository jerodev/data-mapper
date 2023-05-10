<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;

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
            \file_put_contents($mapFileName . '.php', $this->createObjectMappingFunction($class, $mapFileName));
        }

        // Include the function containing file and call the function.
        require_once($mapFileName . '.php');
        return ($mapFileName)($this->mapper, $data);
    }

    private function createObjectMappingFunction(string $class, string $mapFunctionName): string
    {
        $blueprint = $this->classBluePrinter->print($class);

        // Instantiate a new object
        $args = [];
        foreach ($blueprint->constructorArguments as $argument) {
            $arg = '$data[\'' . $argument['name'] . '\']';
            if ($argument['type'] !== null) {
                $arg = $this->castInMapperFunction($argument['type'], $arg);
            }

            $args[] = $arg;
        }
        $content = '$x = new ' . $class . '(' . \implode(', ', $args) . ');';

        // TODO: map properties

        $mapperClass = Mapper::class;
        return <<<PHP
        <?php
        function {$mapFunctionName}({$mapperClass} \$mapper, array \$data)
        {
            {$content};

            return \$x;
        }
        PHP;
    }

    private function castInMapperFunction(DataTypeCollection $type, string $value): string
    {
        if (\count($type->types) === 1) {
            $type = $type->types[0];
            if ($type->isNative()) {
                return match ($type->type) {
                    'null' => 'null',
                    'bool' => "\\filter_var({$value}, \FILTER_VALIDATE_BOOL)",
                    'float' => '(float) ' . $value,
                    'int' => '(int) ' . $value,
                    'string' => '(string) ' . $value,
                    'object' => '(object) ' . $value,
                    default => $value,
                };
            }

            if ($type->isGenericArray()) {
                return '(array) ' . $value;
            }
        }

        return '$mapper->map(\'' . $type->__toString() . '\', ' . $value . ')';
    }
}
