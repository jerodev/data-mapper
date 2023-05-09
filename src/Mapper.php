<?php

namespace Jerodev\DataMapper;

use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeFactory;

class Mapper
{
    private DataTypeFactory $dataTypeFactory;

    public function __construct()
    {
        $this->dataTypeFactory = new DataTypeFactory();
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
            return $this->map(
                $this->dataTypeFactory->fromString($type),
                $data,
            );
        }

        // TODO: more mapping!
    }
}
