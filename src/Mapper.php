<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\UnexpectedNullValueException;
use Jerodev\DataMapper\Models\DataType;

class Mapper
{
    private ObjectMapper $objectMapper;

    public function __construct()
    {
        $this->objectMapper = new ObjectMapper(
            $this,
            new BluePrinter()
        );
    }

    /**
     * Map anything!
     *
     * @param DataType|string $type The type to map to.
     * @param mixed $data Deserialized data to map to the type.
     * @return mixed
     */
    public function map($type, $data)
    {
        if (\is_string($type)) {
            $type = DataType::parse($type);
        }

        if ($data === null) {
            if ($type->isNullable()) {
                return null;
            } else {
                throw new UnexpectedNullValueException();
            }
        }

        if ($type->isArray() && \is_array($data)) {
            return $this->mapArray($type, $data);
        }

        if ($type->isNativeType()) {
            return $this->mapNative($type, $data);
        }

        if (\is_array($data)) {
            return $this->objectMapper->map($type->getType(), $data);
        }

        // TODO: do we allow this?
        return $data;
    }

    /**
     * Map an array of a certain type.
     *
     * @param DataType $type
     * @param array $data
     * @return array
     */
    private function mapArray(DataType $type, array $data): array
    {
        $singleType = $type->clone(false);

        // If the array is sequential, use array_map.
        $keys = \array_keys($data);
        if (\array_keys($keys) === $keys) {
            return \array_map(
                fn($value) => $this->map($singleType, $value),
                $data
            );
        }

        // If we have an associative array, retain the keys.
        $array = [];
        foreach ($data as $key => $value) {
            $array[$key] = $this->map($singleType, $value);
        }

        return $array;
    }

    /**
     * Map a native php type.
     *
     * @param DataType $type
     * @param mixed $data
     * @return mixed
     */
    private function mapNative(DataType $type, $data)
    {
        switch ($type->getType()) {
            case 'array':
                return $data;
            case 'bool':
                return \filter_var($data, FILTER_VALIDATE_BOOLEAN);
            case 'float':
                return \floatval($data);
            case 'int':
                return \intval($data);
            case 'object':
                return (object)$data;
            case 'string':
                return \strval($data);
        }

        // Unknown internal type.
        return $data;
    }
}
