<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapperConfig;
use Jerodev\DataMapper\Objects\ObjectMapper;
use Jerodev\DataMapper\Tests\_Mocks\SelfMapped;
use Jerodev\DataMapper\Tests\_Mocks\SuitEnum;
use Jerodev\DataMapper\Types\DataType;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

final class ObjectMapperTest extends TestCase
{
    #[Test]
    public function it_should_generate_cache_key_based_on_config(): void
    {
        $classname = 'A' . \uniqid();
        $fqcn = '\Jerodev\DataMapper\Tests\_Mocks\\' . $classname;
        $filename = __DIR__ . '/../_Mocks/' . $classname . '.php';
        $classContent = <<<PHP
            <?php

            namespace Jerodev\DataMapper\Tests\_Mocks;

            final class {$classname}
            {
                public int \$id;
            }
            PHP;

        \file_put_contents($filename, $classContent);

        $config = new MapperConfig();
        $config->classMapperDirectory = '{$TMP}/' . $classname;

        $mapper = new Mapper($config);
        $result = $mapper->map($fqcn, ['id' => '8']);
        $this->assertSame(8, $result->id);

        // Calling the mapper again should reuse the mapper function
        $mapper->map($fqcn, ['id' => '10']);
        $mapper->map($fqcn, ['id' => '11']);
        $mapper->map($fqcn, ['id' => '0']);
        $this->assertCount(1, \glob(\sys_get_temp_dir() . '/' . $classname . '/*'));

        // Changing the file content should also not change the mapper function
        \file_put_contents($filename, \str_replace('final ', '', $classContent));
        $mapper->map($fqcn, ['id' => 8]);
        $this->assertCount(1, \glob(\sys_get_temp_dir() . '/' . $classname . '/*'));

        // Changing to md5 cache key should create a new mapper
        $config->classCacheKeySource = 'md5';
        $result = $mapper->map($fqcn, ['id' => '65536']);
        $this->assertSame(65536, $result->id);
        $this->assertCount(2, \glob(\sys_get_temp_dir() . '/' . $classname . '/*'));

        // Changing the file contents should now also create a new mapper
        \file_put_contents($filename, \str_replace('class', 'final class', $classContent));
        $mapper->map($fqcn, ['id' => 8]);
        $this->assertCount(3, \glob(\sys_get_temp_dir() . '/' . $classname . '/*'));

        // Cleanup afterward
        \unlink($filename);
    }

    #[Test]
    public function it_should_let_classes_map_themselves(): void
    {
        $value = (new ObjectMapper(new Mapper()))->map(
            new DataType(SelfMapped::class, true),
            [
                'data' => [
                    'John Doe',
                    'Jane Doe',
                ],
            ],
        );

        $this->assertEquals(['John Doe', 'Jane Doe'], $value->users);
    }

    #[Test]
    public function it_should_not_retain_mapper_files_in_debug_mode(): void
    {
        $config = new MapperConfig();
        $config->debug = true;

        $objectMapper = new ObjectMapper(new Mapper($config));
        $objectMapper->clearCache();
        $objectMapper->map(new DataType(Mapper::class, false), []);

        $filePattern = $objectMapper->mapperDirectory() . '/*.php';
        $this->assertCount(1, \glob($filePattern));

        unset($objectMapper);

        $this->assertEmpty(\glob($filePattern));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_should_throw_on_invalid_enum_value(): void
    {
        $this->expectException(ValueError::class);

        (new ObjectMapper(new Mapper()))->map(
            new DataType(SuitEnum::class, false),
            'x',
        );
    }
}
