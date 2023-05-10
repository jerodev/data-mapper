<?php

namespace Jerodev\DataMapper;

class MapperConfig
{
    /**
     * This is the directly where generated mappers will be stored.
     * It is recommended to prune this directory on every deploy to prevent old mappers from being used.
     * The prefix `{$TMP}` is replaced with the system's temporary directory.
     *
     * @var string
     */
    public string $classMapperDirectory = '{$TMP}' . \DIRECTORY_SEPARATOR . 'mappers';

    /**
     * In debug mode, the generated mapper files are deleted as soon as mapping is done.
     * This let you edit mapped classes without having to worry about the mapper cache.
     *
     * @var bool
     */
    public bool $debug = false;

    /**
     * If true, enums will be mapped using the `tryFrom` method instead of the `from` method.
     * This might result in null values being mapped to non-nullable fields.
     *
     * @var bool
     */
    public bool $enumTryFrom = false;

    /**
     * If true, mapping a null value to a non-nullable field will throw an UnexpectedNullValueException.
     *
     * @var bool
     */
    public bool $strictNullMapping = true;
}
