<?php

namespace Jerodev\DataMapper\Types;

use Jerodev\DataMapper\Exceptions\UnexpectedTokenException;

class DataTypeFactory
{
    /** @var array<string, DataTypeCollection> */
    private array $typeCache = [];

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
