<?php

namespace Jerodev\DataMapper\Tests;

use Generator;
use Jerodev\DataMapper\Mapper;
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
        $this->assertEquals($expected, $this->mapper->map($type, $input));
    }

    /**
     * @test
     * @dataProvider arrayTypeDataProvider
     */
    public function it_should_map_array_types($expected, string $type, $input): void
    {
        $this->assertEquals($expected, $this->mapper->map($type, $input));
    }

    public function nativeTypeDataProvider(): Generator
    {
        yield [['1', '2', '3'], 'array', ['1', '2', '3']];
        yield [['1'], 'array', '1'];
        yield [[], 'array', []];
        yield [['1', '2', '3'], 'iterable', ['1', '2', '3']];
        yield [['1'], 'iterable', '1'];
        yield [[], 'iterable', []];

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
    }

    public function arrayTypeDataProvider(): Generator
    {
        yield [[true, true, false], 'bool[]', ['1', true, 'false']];

        yield [[1, 2, 3], 'float[]', ['1', 2, '3']];
        yield [[1, 2, 3], 'int[]', ['1', 2, '3']];
        yield [['1', '2', '3'], 'string[]', ['1', 2, '3']];
    }
}