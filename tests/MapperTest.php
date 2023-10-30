<?php

namespace Jerodev\DataMapper\Tests;

use Generator;
use Jerodev\DataMapper\Exceptions\UnexpectedNullValueException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapperConfig;
use Jerodev\DataMapper\Tests\_Mocks\Aliases;
use Jerodev\DataMapper\Tests\_Mocks\Constructor;
use Jerodev\DataMapper\Tests\_Mocks\Nullable;
use Jerodev\DataMapper\Tests\_Mocks\SelfMapped;
use Jerodev\DataMapper\Tests\_Mocks\SuitEnum;
use Jerodev\DataMapper\Tests\_Mocks\SuperUserDto;
use Jerodev\DataMapper\Tests\_Mocks\UserDto;
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

    /** @test */
    public function it_should_map_nullable_objects_from_empty_array(): void
    {
        $options = new MapperConfig();
        $options->nullObjectFromEmptyArray = true;
        $options->debug = true;

        $mapper = new Mapper($options);

        // Return null for nullable object
        $this->assertNull($mapper->map('?' . Nullable::class, []));

        // Don't return null if not nullable
        $mapper->clearCache();
        $this->assertInstanceOf(Nullable::class, $mapper->map(Nullable::class, []));
    }

    /**
     * @test
     * @dataProvider objectValuesDataProvider
     */
    public function it_should_map_objects(string $type, mixed $value, mixed $expectation): void
    {
        $config = new MapperConfig();
        $config->classMapperDirectory = __DIR__ . '/..';

        $this->assertEquals($expectation, (new Mapper($config))->map($type, $value));
    }

    /** @test */
    public function it_should_throw_on_unexpected_null_value(): void
    {
        $config = new MapperConfig();
        $config->strictNullMapping = true;

        $this->expectException(UnexpectedNullValueException::class);

        (new Mapper($config))->map('string', null);
    }

    public static function nativeValuesDataProvider(): Generator
    {
        yield ['null', null, null];

        yield ['array', [1, 'b'], [1, 'b']];

        yield ['bool[]', [true, false], [true, false]];
        yield ['bool', '1', true];
        yield ['bool', '', false];

        yield ['float', 6.8, 6.8];
        yield ['float', 5, 5.0];
        yield ['float', '1', 1.0];
        yield ['float', '1.3', 1.3];

        yield ['int', 5, 5];
        yield ['int', '8', 8];
        yield ['int', 8.3, 8];
        yield ['?int', null, null];
        yield ['int|string|null', null, null];
        yield ['int[]', [5, 8], [5, 8]];
        yield ['int[][][][][]', [[[[['5']]]]], [[[[[5]]]]]];
        yield ['array<int[][]>[][]', [[[[['0']]]]], [[[[[0]]]]]];

        yield ['string', 4, '4'];
        yield ['array<string>', [4, 5], ['4', '5']];

        yield ['array<int, ' . SuitEnum::class . '>', ['8.0' => 'H', 9 => 'S'], ['8' => SuitEnum::Hearts, '9' => SuitEnum::Spades]];
    }

    public static function objectValuesDataProvider(): Generator
    {
        yield ['object[]', [['a' => 'b'], ['c' => 'd']], [(object)['a' => 'b'], (object)['c' => 'd']]];
        yield ['Mapper', [], new Mapper()];

        $dto = new UserDto('Jeroen');
        $dto->favoriteSuit = SuitEnum::Diamonds;
        $dto->friends = [
            new UserDto('John'),
            new UserDto('Jane'),
        ];
        yield [
            UserDto::class,
            [
                'name' => 'Jeroen',
                'favoriteSuit' => 'D',
                'friends' => [
                    ['name' => 'john'],
                    ['name' => 'jane'],
                ],
            ],
            $dto
        ];

        $dto = new SuperUserDto('Superman'); // Uppercase because UserDto post mapping
        $dto->canFly = true;
        $dto->stars = 3;    // Increased 3 times by post mapping function
        yield [
            SuperUserDto::class,
            [
                'name' => 'superman',
                'canFly' => true,
            ],
            $dto,
        ];

        $dto = new SelfMapped();
        $dto->users = ['me', ['myself', 'I']];
        yield [
            SelfMapped::class,
            [
                'data' => [
                    'me',
                    [
                        'myself',
                        'I',
                    ],
                ],
            ],
            $dto,
        ];

        $dto = new Aliases();
        $dto->userAliases = [
            'Jerodev' => new UserDto('Jeroen'),
            'Foo' => new UserDto('Bar'),
        ];
        $dto->sm = new SelfMapped();
        $dto->sm->users = ['q'];
        yield [
            Aliases::class,
            [
                'userAliases' => [
                    'Jerodev' => [
                        'name' => 'Jeroen',
                    ],
                    'Foo' => [
                        'name' => 'Bar',
                    ],
                ],
                'sm' => [
                    'data' => ['q'],
                ],
            ],
            $dto,
        ];

        // Allow uninitialized properties
        $dto = new Aliases();
        yield [
            Aliases::class,
            [],
            $dto,
        ];

        // Array in constructor
        $dto = new Constructor([
            new UserDto('Jerodev'),
        ]);
        yield [
            Constructor::class,
            [
                'users' => [
                    [
                        'name' => 'Jerodev',
                    ],
                ],
                'foo' => null,
            ],
            $dto,
        ];

        // Null for nullable properties
        $dto = new UserDto('Joe');
        $dto->score = null;
        yield [
            UserDto::class,
            [
                'name' => 'Joe',
                'score' => null,
            ],
            $dto,
        ];
    }
}
