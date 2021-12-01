<?php

namespace Jerodev\DataMapper\Tests;

use Jerodev\DataMapper\Printers\BluePrinter;
use Jerodev\DataMapper\Exceptions\ConstructorParameterMissingException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapsItself;
use Jerodev\DataMapper\ObjectMapper;
use Jerodev\DataMapper\Tests\TestClasses\ArrayConstructorClass;
use Jerodev\DataMapper\Tests\TestClasses\CallingClass;
use Jerodev\DataMapper\Tests\TestClasses\ConstructorClass;
use Jerodev\DataMapper\Tests\TestClasses\PostMappingClass;
use Jerodev\DataMapper\Tests\TestClasses\RecursiveClass;
use Jerodev\DataMapper\Tests\TestClasses\SimpleClass;
use Jerodev\DataMapper\Tests\TestClasses\UnionTypesClass;
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

    /**
     * @test
     * @requires PHP 8.0
     */
    public function it_should_call_post_mapping_function_after_mapping(): void
    {
        $data = [
            'value' => 2,
        ];

        $object = $this->mapper->map(PostMappingClass::class, $data);

        $this->assertSame(4, $object->value);
    }

    /** @test */
    public function it_should_map_constructor_parameters(): void
    {
        $result = $this->mapper->map(ConstructorClass::class, [
            'foo' => 'foo',
            'baz' => 'boo',
        ]);
        \assert($result instanceof ConstructorClass);

        $this->assertSame('foo', $result->foo);
        $this->assertSame('bar', $result->bar);
        $this->assertSame('boo', $result->baz);
    }

    /** @test */
    public function it_should_map_from_array(): void
    {
        $class = new class implements MapsItself {
            public ?string $foo = null;
            private string $bar;

            public function getBar(): string
            {
                return $this->bar;
            }

            public function setBar(string $bar): void
            {
                $this->bar = $bar;
            }

            public static function mapObject($data, Mapper $mapper): MapsItself
            {
                $object = new self();
                $object->setBar($data['bar']);

                return $object;
            }
        };

        $mapped = $this->mapper->map(\get_class($class), [
            'foo' => 'bar',
            'bar' => 'baz',
        ]);

        $this->assertSame('baz', $mapped->getBar());
        $this->assertNull($mapped->foo);
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
            'numberOrString' => '7',
        ]);

        $this->assertSame('112', $result->extended);
        $this->assertSame('bar', $result->foo);
        $this->assertSame(7, $result->numberOrString);
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

        $this->assertSame('foo', $result->value);
        $this->assertSame('bar', $result->recursive->value);
        $this->assertSame('baz', $result->recursive->recursive->value);
    }

    /** @test */
    public function it_should_map_simple_properties(): void
    {
        $result = $this->mapper->map(SimpleClass::class, [
            'foo' => 'bar',
            'number' => '7',
            'floatingPoint' => '3.14',
            'checkbox' => 1,
            'numbers' => [
                'foo' => [1, '2'],
                'bar' => [3],
            ]
        ]);

        $this->assertSame('bar', $result->foo);
        $this->assertSame(7, $result->number);
        $this->assertSame(3.14, $result->floatingPoint);
        $this->assertTrue($result->checkbox);
        $this->assertSame($result->numbers,  [
            'foo' => [1, 2],
            'bar' => [3],
        ]);
    }

    /**
     * @test
     * @requires PHP 8.0
     */
    public function it_should_map_union_types(): void
    {
        $data = [
            'value' => '5',
            'stringValue' => 'foo',
        ];

        $object = $this->mapper->map(UnionTypesClass::class, $data);

        $this->assertEquals(5, $object->value);
        $this->assertEquals('foo', $object->stringValue);
    }

    /** @test */
    public function it_should_get_constructor_type_from_property_if_possible(): void
    {
        $result = $this->mapper->map(ArrayConstructorClass::class, ['integers' => ['5', '18']]);

        $this->assertSame([5, 18], $result->integers);
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

    /** @test */
    public function it_should_use_calling_class_to_find_type_namespace(): void
    {
        $result = $this->mapper->map(CallingClass::class, [
            'recursive' => [
                [
                    'value' => 'foo',
                ],
            ],
        ]);

        $this->assertInstanceOf(RecursiveClass::class, $result->recursive[0]);
        $this->assertSame('foo', $result->recursive[0]->value);
    }
}
