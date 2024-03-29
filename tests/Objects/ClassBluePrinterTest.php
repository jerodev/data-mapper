<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Generator;
use Jerodev\DataMapper\Objects\ClassBluePrinter;
use Jerodev\DataMapper\Tests\_Mocks\Constructor;
use Jerodev\DataMapper\Tests\_Mocks\SuitEnum;
use Jerodev\DataMapper\Tests\_Mocks\UserDto;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use PHPUnit\Framework\TestCase;

final class ClassBluePrinterTest extends TestCase
{
    /**
     * @test
     * @dataProvider classBlueprintDataProvider
     */
    public function it_should_blueprint_classes(string $className, array $constructorArguments = [], array $properties = []): void
    {
        $blueprint = (new ClassBluePrinter())->print($className);

        $this->assertEquals($constructorArguments, $blueprint->constructorArguments);
        $this->assertEquals($properties, $blueprint->properties);
    }

    public static function classBlueprintDataProvider(): Generator
    {
        yield [
            UserDto::class,
            [
                'name' => [
                    'type' => new DataTypeCollection([
                        new DataType('string', false),
                    ]),
                ],
            ],
            [
                'friends' => [
                    'type' => new DataTypeCollection([
                        new DataType(
                            'array',
                            false,
                            [
                                new DataTypeCollection([
                                    new DataType(UserDto::class, false),
                                ]),
                            ],
                        ),
                    ]),
                    'default' => [],
                ],
                'favoriteSuit' => [
                    'type' => new DataTypeCollection([
                        new DataType(SuitEnum::class, true)
                    ]),
                    'default' => null,
                ],
                'score' => [
                    'type' => new DataTypeCollection([
                        new DataType('int', true)
                    ]),
                ],
            ],
        ];

        yield [
            Constructor::class,
            [
                'users' => [
                    'type' => new DataTypeCollection([
                        new DataType('array', false, [
                            new DataTypeCollection([
                                new DataType('UserDto', false),
                            ]),
                        ]),
                    ]),
                ],
                'foo' => [
                    'type' => new DataTypeCollection([
                        new DataType('bool', true),
                    ]),
                    'default' => null,
                ],
            ],
        ];
    }
}
