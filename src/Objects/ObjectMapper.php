<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Attributes\PostMapping;
use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapsItself;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;
use ReflectionClass;

class ObjectMapper
{
    private const MAPPER_FUNCTION_PREFIX = 'jmapper_';

    private readonly ClassBluePrinter $classBluePrinter;

    public function __construct(
        private readonly Mapper $mapper,
        private readonly DataTypeFactory $dataTypeFactory = new DataTypeFactory(),
    ) {
        $this->classBluePrinter = new ClassBluePrinter();
    }

    /**
     * @param DataType|string $type
     * @param array|string $data
     * @return object|null
     * @throws CouldNotResolveClassException
     */
    public function map(DataType|string $type, array|string $data): ?object
    {
        $class = $this->dataTypeFactory->classResolver->resolve(\is_string($type) ? $type : $type->type);
        if (\is_subclass_of($class, MapsItself::class)) {
            return \call_user_func([$class, 'mapSelf'], $data, $this->mapper);
        }

        // If the data is a string and the class is an enum, create the enum.
        if (\is_string($data) && \is_subclass_of($class, \BackedEnum::class)) {
            if ($this->mapper->config->enumTryFrom) {
                return $class::tryFrom($data);
            }

            return $class::from($data);
        }

        $functionName = self::MAPPER_FUNCTION_PREFIX . \md5($class);
        if ($this->mapper->config->classCacheKeySource === 'md5' || $this->mapper->config->classCacheKeySource === 'modified') {
            $reflection = new ReflectionClass($class);
            $functionName = match ($this->mapper->config->classCacheKeySource) {
                'md5' => self::MAPPER_FUNCTION_PREFIX . \md5_file($reflection->getFileName()),
                'modified' => self::MAPPER_FUNCTION_PREFIX . \md5(\filemtime($reflection->getFileName())),
            };
        }

        $fileName = $this->mapperDirectory() . \DIRECTORY_SEPARATOR . $functionName . '.php';
        if (! \file_exists($fileName)) {
            \file_put_contents(
                $fileName,
                $this->createObjectMappingFunction(
                    $this->classBluePrinter->print($class),
                    $functionName,
                    $type instanceof DataType && $type->isNullable,
                ),
            );
        }

        // Include the function containing file and call the function.
        require_once($fileName);
        return ($functionName)($this->mapper, $data);
    }

    public function clearCache(): void
    {
        foreach (\glob($this->mapperDirectory() . \DIRECTORY_SEPARATOR . self::MAPPER_FUNCTION_PREFIX . '*.php') as $file) {
            \unlink($file);
        }
    }

    public function mapperDirectory(): string
    {
        $dir = \str_replace('{$TMP}', \sys_get_temp_dir(), $this->mapper->config->classMapperDirectory);
        if (! \file_exists($dir) && ! \mkdir($dir, 0777, true) && ! \is_dir($dir)) {
            throw new \RuntimeException("Could not create caching directory '{$dir}'");
        }

        return \rtrim($dir, \DIRECTORY_SEPARATOR);
    }

    private function createObjectMappingFunction(ClassBluePrint $blueprint, string $mapFunctionName, bool $isNullable): string
    {
        $tab = '    ';
        $content = '';

        if ($isNullable && $this->mapper->config->nullObjectFromEmptyArray) {
            $content .= 'if (\$data === []) {' . \PHP_EOL;
            $content .= $tab . 'return null;' . \PHP_EOL;
            $content .= '}' . \PHP_EOL . \PHP_EOL;
        }

        // Instantiate a new object
        $args = [];
        foreach ($blueprint->constructorArguments as $name => $argument) {
            $arg = "\$data['{$name}']";

            if ($argument['type'] !== null) {
                $arg = $this->castInMapperFunction($arg, $argument['type'], $blueprint);
            }

            if (\array_key_exists('default', $argument)) {
                $arg = $this->wrapDefault($arg, $name, $argument['default']);
            }

            $args[] = $arg;
        }
        $content .= '$x = new ' . $blueprint->namespacedClassName . '(' . \implode(', ', $args) . ');';

        // Map properties
        foreach ($blueprint->properties as $name => $property) {
            // Use a foreach to map key/value arrays
            if (\count($property['type']->types) === 1 && $property['type']->types[0]->isArray() && \count($property['type']->types[0]->genericTypes) === 2) {
                $content .= $this->buildPropertyForeachMapping($name, $property, $blueprint);

                continue;
            }

            $propertyMap = $this->castInMapperFunction("\$data['{$name}']", $property['type'], $blueprint);
            if (\array_key_exists('default', $property)) {
                $propertyMap = $this->wrapDefault($propertyMap, $name, $property['default']);
            }

            $propertySet = \PHP_EOL . $tab . $tab . '$x->' . $name . ' = ' . $propertyMap . ';';

            if ($this->mapper->config->allowUninitializedFields && ! \array_key_exists('default', $property)) {
                $propertySet = $this->wrapArrayKeyExists($propertySet, $name);
            }

            $content .= $propertySet;
        }

        // Post mapping functions?
        foreach ($blueprint->classAttributes as $attribute) {
            if ($attribute instanceof PostMapping) {
                if (\is_string($attribute->postMappingCallback)) {
                    $content.= \PHP_EOL . \PHP_EOL . $tab . $tab . "\$x->{$attribute->postMappingCallback}(\$data, \$x);";
                } else {
                    $content.= \PHP_EOL . \PHP_EOL . $tab . $tab . "\call_user_func({$attribute->postMappingCallback}, \$data, \$x);";
                }
            }
        }

        // Render the function
        $mapperClass = Mapper::class;
        return <<<PHP
        <?php

        if (! \\function_exists('{$mapFunctionName}')) {
            function {$mapFunctionName}({$mapperClass} \$mapper, array \$data)
            {
                {$content}

                return \$x;
            }
        }
        PHP;
    }

    private function castInMapperFunction(string $propertyName, DataTypeCollection $type, ClassBluePrint $bluePrint): string
    {
        if (\count($type->types) === 1) {
            $type = $type->types[0];

            if ($type->isNullable) {
                return "{$propertyName} === null ? null : " . $this->castInMapperFunction($propertyName, new DataTypeCollection([$type->removeNullable()]), $bluePrint);
            }

            if ($type->isNative()) {
                return match ($type->type) {
                    'null' => 'null',
                    'bool' => "\\filter_var({$propertyName}, \FILTER_VALIDATE_BOOL)",
                    'float' => '(float) ' . $propertyName,
                    'int' => '(int) ' . $propertyName,
                    'string' => '(string) ' . $propertyName,
                    'object' => '(object) ' . $propertyName,
                    default => $propertyName,
                };
            }

            if ($type->isArray()) {
                if ($type->isGenericArray()) {
                    return '(array) ' . $propertyName;
                }
                if (\count($type->genericTypes) === 1) {
                    $uniqid = \uniqid();
                    return "\\array_map(static fn (\$x{$uniqid}) => " . $this->castInMapperFunction('$x' . $uniqid, $type->genericTypes[0], $bluePrint) . ", {$propertyName})";
                }
            }

            if (\is_subclass_of($type->type, \BackedEnum::class)) {
                $enumFunction = $this->mapper->config->enumTryFrom ? 'tryFrom' : 'from';

                return "{$type->type}::{$enumFunction}({$propertyName})";
            }

            if (\is_subclass_of($type->type, MapsItself::class)) {
                return "{$type->type}::mapSelf({$propertyName}, \$mapper)";
            }

            $className = $this->dataTypeFactory->print($type, $bluePrint->fileName);
            if (\class_exists($className)) {
                return "\$mapper->objectMapper->map('{$className}', {$propertyName})";
            }
        }

        return '$mapper->map(\'' . $this->dataTypeFactory->print($type, $bluePrint->fileName) . '\', ' . $propertyName . ')';
    }

    private function wrapDefault(string $value, string $arrayKey, mixed $defaultValue): string
    {
        if (\str_contains($value, '?')) {
            $value = "({$value})";
        }

        if (\is_object($defaultValue)) {
            $defaultRaw = 'new ' . $defaultValue::class . '()';
        } else {
            $defaultRaw = \var_export($defaultValue, true);
        }

        return "(\\array_key_exists('{$arrayKey}', \$data) ? {$value} : {$defaultRaw})";
    }

    private function wrapArrayKeyExists(string $expression, string $arrayKey): string
    {
        $content  = \PHP_EOL . \str_repeat('    ', 2) . "if (\\array_key_exists('{$arrayKey}', \$data)) {";
        $content .= \str_replace(\PHP_EOL, \PHP_EOL . '    ', $expression) . \PHP_EOL;
        $content .= \str_repeat('    ', 2) . '}';

        return $content;
    }

    public function __destruct()
    {
        if ($this->mapper->config->debug) {
            $this->clearCache();
        }
    }

    /** @param array{type: DataTypeCollection, default?: mixed} $property */
    private function buildPropertyForeachMapping(string $propertyName, array $property, ClassBluePrint $blueprint): string
    {
        $foreach  = \PHP_EOL . \str_repeat('    ', 2) . '$x->' . $propertyName . ' = [];';
        $foreach .= \PHP_EOL . \str_repeat('    ', 2) . 'foreach ($data[\'' . $propertyName . '\'] as $key => $value) {';
        $foreach .= \PHP_EOL . \str_repeat('    ', 3) . '$x->' . $propertyName . '[' . $this->castInMapperFunction('$key', $property['type']->types[0]->genericTypes[0], $blueprint) .  '] = ';
        $foreach .= $this->castInMapperFunction('$value', $property['type']->types[0]->genericTypes[1], $blueprint) . ';';
        $foreach .= \PHP_EOL . \str_repeat('    ', 2) . '}';

        if (\array_key_exists('default', $property) || $this->mapper->config->allowUninitializedFields) {
            $foreach = $this->wrapArrayKeyExists($foreach, $propertyName);
        }

        return $foreach;
    }
}
