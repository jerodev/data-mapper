<?php

namespace Jerodev\DataMapper\Types;

use Jerodev\DataMapper\Exceptions\UnexpectedTokenException;

final class DataTypeFactory
{
    /** @var array<string, DataType> */
    private array $typeCache = [];

    public function fromString(string $rawType): DataTypeCollection
    {
        $types = [];

        $parts = \explode('|', $rawType);
        foreach ($parts as $part) {
            $types[] = $this->singleFromString(\trim($part));
        }

        return new DataTypeCollection($types);
    }

    public function singleFromString(string $rawType): DataType
    {
        if (\array_key_exists($rawType, $this->typeCache)) {
            return $this->typeCache[$rawType];
        }

        $tokens = $this->tokenize($rawType);
        if (\count($tokens) === 1) {
            return $this->typeCache[$rawType] = new DataType($rawType, false);
        }

        return $this->typeCache[$rawType] = $this->parseTokens($tokens);
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

            if (\in_array($char, ['<', '>', '?', ','])) {
                if (! empty($token)) {
                    $tokens[] = $token;
                }
                $tokens[] = $char;
                $token = '';
            } else if ($char === '[') {
                $tokens[] = $token;
                $token = $char;
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
     * @return DataType
     */
    private function parseTokens(array $tokens): DataType
    {
        $isNullable = $tokens[0] === '?';
        if ($isNullable) {
            \array_shift($tokens);
        }

        $type = \array_shift($tokens);

        // If nothing is left, return the type
        if (empty($tokens)) {
            return new DataType($type, $isNullable);
        }

        // If square brackets, it's an array with the type as value
        $nextToken = \array_shift($tokens);
        if ($nextToken === '[]') {
            return new DataType('array', $isNullable, [new DataTypeCollection([new DataType($type, false)])]);
        }

        // If beaks, find either a value or a key and value
        if ($nextToken === '<') {
            $nextGenericType = '';
            $genericTypes = [];
            while ($nextToken = \array_shift($tokens)) {
                if ($nextToken === '>') {
                    break;
                }

                if ($nextToken === ',') {
                    $genericTypes[] = $this->fromString($nextGenericType);
                    $nextGenericType = '';

                    continue;
                }

                $nextGenericType .= \trim($nextToken);
            }

            if (empty($genericTypes) && empty($nextGenericType)) {
                throw new \Exception('Found generic type without subtype');
            }

            if (! empty($nextGenericType)) {
                $genericTypes[] = $this->fromString($nextGenericType);
            }

            return new DataType($type, $isNullable, $genericTypes);
        }

        throw new UnexpectedTokenException($nextToken);
    }
}
