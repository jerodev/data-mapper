<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Exceptions\UnexpectedNullValueException;
use Jerodev\DataMapper\Models\DataType;
use Jerodev\DataMapper\Models\MapperOptions;
use Jerodev\DataMapper\Printers\BluePrinter;

class Mapper
{
    private MapperOptions $mapperOptions;
    private ObjectMapper $objectMapper;

    public function __construct(
        ?MapperOptions $mapperOptions = null
    ) {
        $this->mapperOptions = $mapperOptions ?? new MapperOptions();
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
        if ($type === 'null') {
            return null;
        }

        if (\is_string($type)) {
            $type = DataType::parse($type);
        }

        if ($data === null) {
            if ($type->isNullable() || (! $type->isNullable() && $this->mapperOptions->strictNullMapping === false)) {
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

        return $this->objectMapper->map($type->getType(), $data);
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
        $singleType = $type->getArrayChildType();

        $array = [];
        foreach ($data as $key => $value) {
            // If we have a nested array, keep calling this function.
            if (\is_array($value) && $singleType->isArray()) {
                $array[$key] = self::mapArray($singleType, $value);
            } else {
                $array[$key] = $this->map($singleType, $value);
            }
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
        if ($type->isGenericArray()) {
            return (array) $data;
        }

        switch ($type->getType()) {
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
