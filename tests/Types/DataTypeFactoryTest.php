<?php

namespace Jerodev\DataMapper\Tests\Types;

use Generator;
use Jerodev\DataMapper\Exceptions\UnexpectedTokenException;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;
use PHPUnit\Framework\TestCase;

final class DataTypeFactoryTest extends TestCase
{
    /**
     * @test
     * @dataProvider singleDataTypeProvider
     */
    public function it_should_parse_single_data_types(string $input, DataType $expectation): void
    {
        $this->assertEquals($expectation, (new DataTypeFactory())->singleFromString($input));
    }

    /**
     * @test
     * @dataProvider unexpectedTokenDataProvider
     */
    public function it_should_throw_unexpected_token(string $input): void
    {
        $this->expectException(UnexpectedTokenException::class);

        (new DataTypeFactory())->singleFromString($input);
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
    }
}
