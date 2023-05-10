<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Generator;
use Jerodev\DataMapper\Objects\ClassBluePrinter;
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
                [
                    'name' => 'name',
                    'type' => new DataTypeCollection([
                        new DataType('string', false),
                    ]),
                ],
            ],
            [
                'name' => [
                    'type' => new DataTypeCollection([
                        new DataType('string', false),
                    ]),
                ],
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
            ],
        ];
    }
}
