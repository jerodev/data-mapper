<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapperConfig;
use Jerodev\DataMapper\Objects\ObjectMapper;
use Jerodev\DataMapper\Tests\_Mocks\SuitEnum;
use Jerodev\DataMapper\Types\DataType;
use PHPUnit\Framework\TestCase;
use ValueError;

class ObjectMapperTest extends TestCase
{
    /** @test */
    public function it_should_not_retain_mapper_files_in_debug_mode(): void
    {
        $config = new MapperConfig();
        $config->debug = true;

        $objectMapper = new ObjectMapper(new Mapper($config));
        $objectMapper->map(new DataType(Mapper::class, false), []);
        unset($objectMapper);

        $this->assertEmpty(\glob($config->classMapperDirectory . '/*.php'));
    }

    /** @test */
    public function it_should_parse_enum_using_tryfrom_if_configured(): void
    {
        $config = new MapperConfig();
        $config->enumTryFrom = true;

        $value = (new ObjectMapper(new Mapper($config)))->map(
            new DataType(SuitEnum::class, true),
            'x',
        );

        $this->assertNull($value);
    }

    /** @test */
    public function it_should_save_mappers_in_cache_directory(): void
    {
        $config = new MapperConfig();
        $config->classMapperDirectory = __DIR__ . '/' . \uniqid();

        (new Mapper($config))->map(MapperConfig::class, []);

        $this->assertCount(
            1,
            $files = \glob($config->classMapperDirectory . '/*.php'),
        );

        // Clean up generated files
        \unlink($files[0]);
        \rmdir($config->classMapperDirectory);
    }

    /** @test */
    public function it_should_throw_on_invalid_enum_value(): void
    {
        $this->expectException(ValueError::class);

        (new ObjectMapper(new Mapper()))->map(
            new DataType(SuitEnum::class, false),
            'x',
        );
    }
}
