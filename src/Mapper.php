<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\CouldNotMapValueException;
use Jerodev\DataMapper\Objects\ObjectMapper;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;

class Mapper
{
    private DataTypeFactory $dataTypeFactory;
    private ObjectMapper $objectMapper;

    public function __construct()
    {
        $this->dataTypeFactory = new DataTypeFactory();
        $this->objectMapper = new ObjectMapper();
    }

    /**
     * Map anything!
     *
     * @param DataTypeCollection|string $typeCollection The type to map to.
     * @param mixed $data Deserialized data to map to the type.
     * @return mixed
     */
    public function map($typeCollection, $data)
    {
        if (\is_string($typeCollection)) {
            return $this->map(
                $this->dataTypeFactory->fromString($typeCollection),
                $data,
            );
        }

        if ($data === 'null' && $typeCollection->isNullable()) {
            return null;
        }

        // Loop over all possible types and parse to the first one that matches
        foreach ($typeCollection->types as $type) {
            try {
                if ($type->isNative()) {
                    return $this->mapNativeType($type, $data);
                }

                if ($type->isArray()) {
                    return $this->mapArray($type, $data);
                }

                return $this->mapObject($type, $data);
            } catch (CouldNotMapValueException) {
                continue;
            }
        }

        throw new CouldNotMapValueException($data, $typeCollection);
    }

    private function mapNativeType(DataType $type, mixed $data): float|object|bool|int|string|null
    {
        return match ($type->type) {
            'null' => null,
            'bool' => \filter_var($data, \FILTER_VALIDATE_BOOL),
            'int' => (int) $data,
            'float' => (float) $data,
            'string' => (string) $data,
            'object' => (object) $data,
            default => throw new CouldNotMapValueException($data, $type),
        };
    }

    private function mapArray(DataType $type, mixed $data): array
    {
        if (! \is_iterable($data)) {
            throw new CouldNotMapValueException($data, $type);
        }

        if ($type->isGenericArray()) {
            return (array) $data;
        }

        $keyType = null;
        $valueType = $type->genericTypes[0];
        if (\count($type->genericTypes) > 1) {
            [$keyType, $valueType] = $type->genericTypes;
        }

        $mappedArray = [];
        foreach ($data as $key => $value) {
            if ($keyType !== null) {
                $key = $this->map($keyType, $key);
            }

            $mappedArray[$key] = $this->map($valueType, $value);
        }

        return $mappedArray;
    }

    private function mapObject(DataType $type, mixed $data): ?object
    {
        if ($type->isNullable && $data === null) {
            return null;
        }

        return $this->objectMapper->map($type, $data);
    }
}
