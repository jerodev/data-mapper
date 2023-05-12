<?php

namespace Jerodev\DataMapper\Tests\Types;

use Generator;
use Jerodev\DataMapper\Types\DataTypeFactory;
use PHPUnit\Framework\TestCase;

final class DataTypeCollectionTest extends TestCase
{
    /**
     * @test
     * @dataProvider typeToStringDataProvider
     */
    public function it_should_parse_and_convert_back_to_string(string $input, string $expectation): void
    {
        $this->assertEquals(
            $expectation,
            (new DataTypeFactory())->fromString($input)->__toString(),
        );
    }

    public static function typeToStringDataProvider(): Generator
    {
        yield ['string', 'string'];
        yield ['string|int', 'string|int'];
        yield ['string|null|int', 'string|int|null'];   // null is always parsed last
        yield ['string[]', 'array<string>'];
        yield ['string[][][]', 'array<array<array<string>>>'];
        yield ['array<int[][]>[][]', 'array<array<array<array<array<int>>>>>'];
        yield ['array<int, string>', 'array<int, string>'];
        yield ['Generic<?K, V|bool>', 'Generic<?K, V|bool>'];
        yield ['array<string|int, array<K[]>>', 'array<string|int, array<array<K>>>'];
        yield ['array< int,  array< string | int | null | float     > >', 'array<int, array<string|int|float|null>>'];
    }
}
