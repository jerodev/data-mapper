<?php

namespace Jerodev\DataMapper;

class MapperConfig
{
    /**
     * If disabled, all properties of an object must either be present in the data array or have a default value for
     * mapping to succeed.
     * If enabled, properties that are not present in the data array will be left uninitialized.
     */
    public bool $allowUninitializedFields = true;

    /**
     * This configures how the class mapper cache key is generated.
     * Using a different key type can make sure the mapper does not fail when the class changes, however it can be a bit
     * slower than other types.
     * - fqcn: creates a hash based on the namespaced class name but does not check for changes
     * - md5: creates an md5 hash from the file. This will change if the class changes, but is also slower than fqcn
     * - modified: creates an md5 hash from the file's modification time.
     * @phpstan-var 'fqcn'|'md5'|'modified'
     */
    public string $classCacheKeySource = 'fqcn';

    /**
     * This is the directly where generated mappers will be stored.
     * It is recommended to prune this directory on every deploy to prevent old mappers from being used.
     * The prefix `{$TMP}` is replaced with the system's temporary directory.
     */
    public string $classMapperDirectory = '{$TMP}' . \DIRECTORY_SEPARATOR . 'mappers';

    /**
     * In debug mode, the generated mapper files are deleted as soon as mapping is done.
     * This let you edit mapped classes without having to worry about the mapper cache.
     */
    public bool $debug = false;

    /**
     * If true, enums will be mapped using the `tryFrom` method instead of the `from` method.
     * This might result in null values being mapped to non-nullable fields.
     */
    public bool $enumTryFrom = false;

    /**
     * If true, mapping a null value to a non-nullable field will throw an UnexpectedNullValueException.
     */
    public bool $strictNullMapping = true;
}
