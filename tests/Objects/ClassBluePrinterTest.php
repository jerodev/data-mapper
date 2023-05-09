<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Generator;
use Jerodev\DataMapper\Objects\ClassBluePrinter;
use Jerodev\DataMapper\Tests\_Mocks\UserDto;
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
            [['name' => 'name', 'type' => 'string']],
            ['name' => 'string', 'friends' => 'array<UserDto>'],
        ];
    }
}
