<?php

namespace Jerodev\DataMapper\Tests;

use Generator;
use Jerodev\DataMapper\Mapper;
use PHPUnit\Framework\TestCase;

final class MapperTest extends TestCase
{
    /**
     * @test
     * @dataProvider nativeValuesDataProvider
     */
    public function it_should_map_native_values(string $type, mixed $value, mixed $expectation): void
    {
        $this->assertSame($expectation, (new Mapper())->map($type, $value));
    }

    public static function nativeValuesDataProvider(): Generator
    {
        yield ['Mapper', [], null];

        yield ['null', null, null];

        yield ['array', [1, 'b'], [1, 'b']];

        yield ['bool[]', [true, false], [true, false]];
        yield ['bool', '1', true];
        yield ['bool', null, false];

        yield ['float', 6.8, 6.8];
        yield ['float', 5, 5.0];
        yield ['float', '1', 1.0];
        yield ['float', '1.3', 1.3];

        yield ['int', 5, 5];
        yield ['int', '8', 8];
        yield ['int', 8.3, 8];
        yield ['int[]', [5, 8], [5, 8]];

        yield ['string', 4, '4'];
        yield ['array<string>', [4, 5], ['4', '5']];
    }
}
