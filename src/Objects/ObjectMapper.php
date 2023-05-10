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
            if ($argument['type'] !== null) {
                $arg = $this->castInMapperFunction($argument, $argument['name']);
            }

            $args[] = $arg;
        }
        $content = '$x = new ' . $class . '(' . \implode(', ', $args) . ');';

        foreach ($blueprint->properties as $name => $property) {
            $content.= \PHP_EOL . '    $x->' . $name . ' = ' . $this->castInMapperFunction($property, $name) . ';';
        }

        $mapperClass = Mapper::class;
        return <<<PHP
        <?php
        function {$mapFunctionName}({$mapperClass} \$mapper, array \$data)
        {
            {$content}

            return \$x;
        }
        PHP;
    }

    /**
     * @param array{type: DataTypeCollection, default?: mixed} $property
     * @param string $propertyName
     * @return string
     */
    private function castInMapperFunction(array $property, string $propertyName): string
    {
        $value = "\$data['{$propertyName}']";
        $newValue = null;

        if (\count($property['type']->types) === 1) {
            $type = $property['type']->types[0];
            if ($type->isNative()) {
                $newValue = match ($type->type) {
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
                $newValue = '(array) ' . $value;
            }

            if (\is_subclass_of($type->type, \BackedEnum::class)) {
                $newValue = "{$type->type}::from({$value})";
            }
        }

        if ($newValue === null) {
            $newValue = '$mapper->map(\'' . $property['type']->__toString() . '\', ' . $value . ')';
        }

        if (\array_key_exists('default', $property)) {
            $newValue = "(\\array_key_exists('{$propertyName}', \$data) ? {$newValue} : " . \var_export($property['default'], true) . ')';
        }

        return $newValue;
    }
}
