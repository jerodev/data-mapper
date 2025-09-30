<?php

namespace Jerodev\DataMapper\Tests\Types;

use Generator;
use Jerodev\DataMapper\Exceptions\UnexpectedTokenException;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DataTypeFactoryTest extends TestCase
{
    #[Test]
    #[DataProvider('singleDataTypeProvider')]
    public function it_should_parse_single_data_types(string $input, DataType $expectation): void
    {
        $this->assertEquals($expectation, (new DataTypeFactory())->fromString($input)->types[0]);
    }

    #[Test]
    #[DataProvider('unexpectedTokenDataProvider')]
    public function it_should_throw_unexpected_token(string $input): void
    {
        $this->expectException(UnexpectedTokenException::class);

        (new DataTypeFactory())->fromString($input);
    }

    #[Test]
    #[DataProvider('typeToStringDataProvider')]
    public function it_should_parse_and_convert_back_to_string(string $input, string $expectation): void
    {
        $factory = new DataTypeFactory();

        $this->assertEquals(
            $expectation,
            $factory->print($factory->fromString($input)),
        );
    }

    public static function singleDataTypeProvider(): Generator
    {
        yield ['int', new DataType('int', false)];

        yield ['string[]', new DataType(
            'array',
            false,
            [
                new DataTypeCollection([
                    new DataType('string', false),
                ]),
            ],
        )];
        yield ['array<string>', new DataType(
            'array',
            false,
            [
                new DataTypeCollection([
                    new DataType('string', false),
                ]),
            ],
        )];
        yield ['array<string[]>', new DataType(
            'array',
            false,
            [
                new DataTypeCollection([
                    new DataType('array', false, [
                        new DataTypeCollection([
                            new DataType('string', false),
                        ]),
                    ]),
                ]),
            ],
        )];
        yield ['array<string|int, array<K>>', new DataType(
            'array',
            false,
            [
                new DataTypeCollection([
                    new DataType('string', false),
                    new DataType('int', false),
                ]),
                new DataTypeCollection([
                    new DataType('array', false, [
                        new DataTypeCollection([
                            new DataType('K', false),
                        ]),
                    ]),
                ]),
            ],
        )];

        yield ['Generic<K|bool, ?V>', new DataType(
            'Generic',
            false,
            [
                new DataTypeCollection([
                    new DataType('K', false),
                    new DataType('bool', false),
                ]),
                new DataTypeCollection([
                    new DataType('V', true),
                ]),
            ],
        )];

        yield ['Illuminate\\Collection\\LazyCollection<string, Foo>', new DataType(
            'Illuminate\\Collection\\LazyCollection',
            false,
            [
                new DataTypeCollection([
                    new DataType('string', false),
                ]),
                new DataTypeCollection([
                    new DataType('Foo', false),
                ]),
            ],
        )];
    }

    public static function unexpectedTokenDataProvider(): Generator
    {
        yield ['String>'];
        yield ['array[foo]'];
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
