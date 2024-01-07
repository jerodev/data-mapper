<?php

namespace Jerodev\DataMapper\Types;

use Jerodev\DataMapper\Exceptions\InvalidTypeException;
use Jerodev\DataMapper\Exceptions\UnexpectedTokenException;
use Jerodev\DataMapper\Objects\ClassResolver;

class DataTypeFactory
{
    /** @var array<string, DataTypeCollection> */
    private array $typeCache = [];

    public function __construct(
        public readonly ClassResolver $classResolver = new ClassResolver(),
    ) {
    }

    /**
     * @throws InvalidTypeException
     * @throws UnexpectedTokenException
     */
    public function fromString(string $rawType, bool $forceNullable = false): DataTypeCollection
    {
        $rawType = \trim($rawType);

        if (\array_key_exists($rawType, $this->typeCache)) {
            return $this->typeCache[$rawType];
        }

        $tokens = $this->tokenize($rawType);
        if (\count($tokens) === 1) {
            return $this->typeCache[$rawType] = new DataTypeCollection([
                new DataType($rawType, $forceNullable),
            ]);
        }

        // Parse tokens and make sure nothing is left.
        $type = $this->parseTokens($tokens);
        if (! empty($tokens)) {
            throw new UnexpectedTokenException(\array_shift($tokens));
        }

        return $this->typeCache[$rawType] = $type;
    }

    /**
     * Prints a type with resolved class names, if possible.
     *
     * @param DataTypeCollection|DataType $type
     * @param string|null $sourceFile
     * @return string
     */
    public function print(DataTypeCollection|DataType $type, ?string $sourceFile = null): string
    {
        if ($type instanceof DataType) {
            $type = new DataTypeCollection([$type]);
        }
        \assert($type instanceof DataTypeCollection);

        $types = [];
        foreach ($type->types as $t) {
            $typeString = $t->type;

            // Attempt to resolve the class name
            try {
                $typeString = $t->isNative() || $t->isArray()
                    ? $typeString
                    : $this->classResolver->resolve($t->type, $sourceFile);
            } catch (\Throwable) {
            }

            // Add generic types between < >
            if (\count($t->genericTypes) > 0) {
                $typeString .= '<' . \implode(
                    ', ',
                    \array_map(
                        fn (DataTypeCollection $c) => $this->print($c, $sourceFile),
                        $t->genericTypes,
                    ),
                ) . '>';
            }

            if ($t->isNullable) {
                $typeString = '?' . $typeString;
            }

            $types[] = $typeString;
        }

        return \implode('|', $types);
    }

    /**
     * @param string $type
     * @return array<string>
     */
    private function tokenize(string $type): array
    {
        $tokens = [];
        $token = '';

        for ($i = 0; $i < \strlen($type); $i++) {
            $char = $type[$i];
            if ($char === ' ') {
                continue;
            }

            if (\in_array($char, ['<', '>', '?', ',', '|'])) {
                if (! empty(\trim($token))) {
                    $tokens[] = $token;
                }
                $tokens[] = $char;
                $token = '';
            } else if ($char === '[') {
                if (! empty(\trim($token))) {
                    $tokens[] = $token;
                }
                $token = '';
                if ($type[++$i] === ']') {
                    $tokens[] = '[]';
                    continue;
                }

                throw new UnexpectedTokenException('[');
            } else if ($char === '{') {
                if ($token !== 'array') {
                    throw new UnexpectedTokenException('{');
                }

                $tokens[] = $token;
                $token = '';
                for (; $i < \strlen($type); $i++) {
                    $token .= $type[$i];
                    if ($type[$i] === '}') {
                        break;
                    }
                }

                if (! \str_ends_with($token, '}')) {
                    throw new InvalidTypeException($type, 'Missing closing }');
                }

                $tokens[] = $token;
                $token = '';
            } else {
                $token .= $char;
            }
        }

        if ($token !== '') {
            $tokens[] = $token;
        }

        return $tokens;
    }

    /**
     * @param array<string> $tokens
     * @return DataTypeCollection
     */
    private function parseTokens(array &$tokens): DataTypeCollection
    {
        $types = [];

        $stringStack = '';
        $nullable = false;

        while ($token = \array_shift($tokens)) {
            if ($token === '<') {
                $genericTypes = $this->fetchGenericTypes($tokens);
                while (($tokens[0] ?? '') === '[]') {
                    $stringStack .= \array_shift($tokens);
                }
                $types[] = $this->makeDataType(
                    $stringStack,
                    $nullable,
                    $genericTypes,
                );
                $stringStack = '';
                $nullable = false;

                continue;
            }

            if ($token === '|') {
                $types[] = $this->makeDataType($stringStack, $nullable);
                $stringStack = '';
                $nullable = false;

                continue;
            }

            if ($token === '?') {
                $nullable = true;
                continue;
            }

            if (\str_starts_with($token, '{')) {
                $types[] = new DataType('array', $nullable, arrayFields: $token);
                $stringStack = '';
                $nullable = false;

                continue;
            }

            if (\in_array($token, ['>', ','])) {
                throw new UnexpectedTokenException($token);
            }

            $stringStack .= $token;
        }

        if (! empty($stringStack)) {
            $types[] = $this->makeDataType($stringStack, $nullable);
        }

        return new DataTypeCollection($types);
    }

    /**
     * @param array<string> $tokens
     * @return array<DataTypeCollection>
     */
    private function fetchGenericTypes(array &$tokens): array
    {
        $types = [];
        $collection = [];
        $stringStack = '';
        $nullable = false;
        while ($token = \array_shift($tokens)) {
            if ($token === '<') {
                $types[] = $this->makeDataType(
                    $stringStack,
                    $nullable,
                    $this->fetchGenericTypes($tokens),
                );
                $stringStack = '';
                $nullable = false;

                continue;
            }

            if ($token === ',' || $token === '|') {
                if (! empty($stringStack)) {
                    $types[] = $this->makeDataType($stringStack, $nullable);
                }

                if ($token === ',') {
                    $collection[] = new DataTypeCollection($types);
                    $types = [];
                }

                $stringStack = '';
                $nullable = false;
                continue;
            }

            if ($token === '>') {
                break;
            }

            if ($token === '?') {
                $nullable = true;
                continue;
            }

            if (\str_starts_with($token, '{')) {
                $types[] = new DataType('array', $nullable, arrayFields: $token);
                $stringStack = '';
                $nullable = false;

                continue;
            }

            $stringStack .= $token;
        }

        if (! empty($stringStack)) {
            $types[] = $this->makeDataType($stringStack, $nullable);
        }
        if (! empty($types)) {
            $collection[] = new DataTypeCollection($types);
        }

        return $collection;
    }

    /**
     * @param string $type
     * @param bool $nullable
     * @param array<DataTypeCollection> $genericTypes
     * @return DataType
     */
    private function makeDataType(string $type, bool $nullable = false, array $genericTypes = []): DataType
    {
        // Parse a stack of [] arrays
        $arrayStack = 0;
        while (\str_ends_with($type, '[]')) {
            $type = \substr($type, 0, -2);
            $arrayStack++;
        }
        if ($arrayStack > 0) {
            $type = new DataType($type, false, $genericTypes);
            for ($i = 0; $i < $arrayStack; $i++) {
                $type = new DataType(
                    'array',
                    $arrayStack - $i === 1 ? $nullable : false,
                    [
                        new DataTypeCollection([$type]),
                    ],
                );
            }

            return $type;
        }

        return new DataType($type, $nullable, $genericTypes);
    }
}
