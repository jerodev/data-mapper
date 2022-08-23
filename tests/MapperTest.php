<?php

namespace Jerodev\DataMapper\Tests;

use Generator;
use Jerodev\DataMapper\Exceptions\UnexpectedNullValueException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\Models\MapperOptions;
use Jerodev\DataMapper\Tests\TestClasses\SimpleClass;
use Jerodev\DataMapper\Tests\TestClasses\StatusEnum;
use PHPUnit\Framework\TestCase;

final class MapperTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new Mapper();
    }

    /**
     * @test
     * @dataProvider nativeTypeDataProvider
     */
    public function it_should_map_native_types($expected, string $type, $input): void
    {
        $this->assertSame($expected, $this->mapper->map($type, $input));
    }

    /**
     * @test
     * @dataProvider arrayTypeDataProvider
     */
    public function it_should_map_array_types($expected, string $type, $input): void
    {
        $this->assertEquals($expected, $this->mapper->map($type, $input));
    }

    /** @test */
    public function it_should_map_nullable_object_as_null(): void
    {
        $class = new class {
            public string $foo;
        };

        $mapped = $this->mapper->map('?' . \get_class($class), null);

        $this->assertNull($mapped);
    }

    /** @test */
    public function it_should_throw_exception_when_strict_mapping_null_values(): void
    {
        $this->expectException(UnexpectedNullValueException::class);

        $this->mapper->map('int', null);
    }

    /** @test */
    public function it_should_allow_non_strict_null_mapping_through_options(): void
    {
        $options = new MapperOptions();
        $options->strictNullMapping = false;

        $this->mapper = new Mapper($options);
        $this->assertNull($this->mapper->map('int', null));
    }

    public function nativeTypeDataProvider(): Generator
    {
        yield [['1', '2', '3'], 'array', ['1', '2', '3']];
        yield [['1'], 'array', '1'];
        yield [[], 'array', []];
        yield [['1', '2', '3'], 'iterable', ['1', '2', '3']];
        yield [['1'], 'iterable', '1'];
        yield [[], 'iterable', []];
        yield [[['1']], 'string[][]', [[1]]];
        yield [['first' => [1], 'second' => [2, 3]], 'int[][]', ['first' => ['1'], 'second' => ['2', '3']]];

        yield [true, 'bool', 'true'];
        yield [true, 'bool', 1];
        yield [false, 'bool', 'false'];
        yield [false, 'bool', 0];

        yield [5.7, 'float', '5.7'];
        yield [5.8, 'float', 5.8];

        yield [5, 'int', '5'];
        yield [6, 'int', 6];

        yield ['foo', 'string', 'foo'];
        yield ['7', 'string', 7];

        yield [
            StatusEnum::Success,
            StatusEnum::class,
            'Success',
        ];
    }

    public function arrayTypeDataProvider(): Generator
    {
        yield [[true, true, false], 'bool[]', ['1', true, 'false']];

        yield [[1, 2, 3], 'float[]', ['1', 2, '3']];
        yield [[1, 2, 3], 'int[]', ['1', 2, '3']];
        yield [['1', '2', '3'], 'string[]', ['1', 2, '3']];
        yield [['1', '2', '3'], 'string[]', ['1', 2, '3']];
        yield [['a' => 'b', 'b' => 'c', 4 => '3'], 'array<string>', ['a' => 'b', 'b' => 'c', 4 => 3]];
        yield [[7 => 'b', 6 => 'c', 4 => '3'], 'array<int, string>', ['7' => 'b', '6' => 'c', 4 => 3]];
        yield [[7 => ['b'], 6 => ['c', '3']], 'array<int, string[]>', ['7' => ['b'], '6' => ['c', 3]]];

        $object = new SimpleClass();
        $object->foo = 'bar';
        $object2 = new SimpleClass();
        $object2->foo = 'baz';
        yield [['bar' => $object, 'baz' => $object2], 'array<string, ' . SimpleClass::class . '>', ['bar' => ['foo' => 'bar'], 'baz' => ['foo' => 'baz']]];
        yield [[$object, $object2], 'array<' . SimpleClass::class . '>', [['foo' => 'bar'], ['foo' => 'baz']]];
    }
}