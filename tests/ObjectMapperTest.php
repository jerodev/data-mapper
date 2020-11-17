<?php

namespace Jerodev\DataMapper\Tests;

use Jerodev\DataMapper\BluePrinter;
use Jerodev\DataMapper\Exceptions\ConstructorParameterMissingException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\ObjectMapper;
use Jerodev\DataMapper\Tests\TestClasses\ArrayConstructorClass;
use Jerodev\DataMapper\Tests\TestClasses\ConstructorClass;
use Jerodev\DataMapper\Tests\TestClasses\RecursiveClass;
use Jerodev\DataMapper\Tests\TestClasses\SimpleClass;
use PHPUnit\Framework\TestCase;

final class ObjectMapperTest extends TestCase
{
    private ObjectMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new ObjectMapper(
            new Mapper(),
            new BluePrinter(),
        );
    }

    /** @test */
    public function it_should_map_constructor_parameters(): void
    {
        $result = $this->mapper->map(ConstructorClass::class, [
            'foo' => 'foo',
            'baz' => 'boo',
        ]);
        \assert($result instanceof ConstructorClass);

        $this->assertEquals('foo', $result->foo);
        $this->assertEquals('bar', $result->bar);
        $this->assertEquals('boo', $result->baz);
    }

    /** @test */
    public function it_should_map_parent_class_properties(): void
    {
        $class = new class extends SimpleClass {
            public string $extended;
        };

        $result = $this->mapper->map(\get_class($class), [
            'extended' => 112,
            'foo' => 'bar',
        ]);

        $this->assertEquals('112', $result->extended);
        $this->assertEquals('bar', $result->foo);
    }

    /** @test */
    public function it_should_map_recursive_classes(): void
    {
        $result = $this->mapper->map(RecursiveClass::class, [
            'value' => 'foo',
            'recursive' => [
                'value' => 'bar',
                'recursive' => [
                    'value' => 'baz',
                    'recursive' => null,
                ],
            ],
        ]);
        \assert($result instanceof RecursiveClass);

        $this->assertEquals('foo', $result->value);
        $this->assertEquals('bar', $result->recursive->value);
        $this->assertEquals('baz', $result->recursive->recursive->value);
    }

    /** @test */
    public function it_should_map_simple_properties(): void
    {
        $result = $this->mapper->map(SimpleClass::class, [
            'foo' => 'bar',
            'number' => '7',
            'floatingPoint' => '3.14',
            'checkbox' => 1,
        ]);

        $this->assertEquals('bar', $result->foo);
        $this->assertEquals(7, $result->number);
        $this->assertEquals(3.14, $result->floatingPoint);
        $this->assertTrue($result->checkbox);
    }

    /** @test */
    public function it_should_get_constructor_type_from_property_if_possible(): void
    {
        $result = $this->mapper->map(ArrayConstructorClass::class, ['integers' => ['5', '18']]);

        $this->assertEquals([5, 18], $result->integers);
    }

    /** @test */
    public function it_should_throw_error_on_missing_constructor_parameter(): void
    {
        $this->expectException(ConstructorParameterMissingException::class);

        $this->mapper->map(ConstructorClass::class, [
            'bar' => 'baa',
            'baz' => 'boo',
        ]);
    }
}
