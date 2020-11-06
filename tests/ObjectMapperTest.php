<?php

namespace Jerodev\DataMapper\Tests;

use Jerodev\DataMapper\BluePrinter;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\ObjectMapper;
use Jerodev\DataMapper\Tests\TestClasses\ExtendedClass;
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
    public function it_should_map_parent_class_properties(): void
    {
        $result = $this->mapper->map(ExtendedClass::class, [
            'extended' => 112,
            'foo' => 'bar',
        ]);
        \assert($result instanceof ExtendedClass);

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
}
