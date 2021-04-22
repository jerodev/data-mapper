<?php

namespace Printers;

use Jerodev\DataMapper\Models\ClassBluePrint;
use Jerodev\DataMapper\Models\MethodParameter;
use Jerodev\DataMapper\ObjectMapper;
use Jerodev\DataMapper\Printers\BluePrinter;
use Jerodev\DataMapper\Printers\ClassNamePrinter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClassNamePrinterTest extends TestCase
{
    /** @test */
    public function it_should_resolve_already_fully_qualified_classes(): void
    {
        $this->assertEquals(
            '\Exception',
            $this->printClassName('\Exception')
        );
    }

    /** @test */
    public function it_should_resolve_class_name_from_parent_imports(): void
    {
        $this->assertEquals(
            BluePrinter::class,
            $this->printClassName('BluePrinter', new ReflectionClass(ObjectMapper::class))
        );
    }

    /** @test */
    public function it_should_resolve_class_name_from_parent_namespace(): void
    {
        $this->assertEquals(
            MethodParameter::class,
            $this->printClassName('MethodParameter', new ReflectionClass(ClassBluePrint::class))
        );
    }

    /** @test */
    public function it_should_return_null_on_no_match_found(): void
    {
        $this->assertNull($this->printClassName('\Foo\Bar'));
        $this->assertNull($this->printClassName('\Foo\Bar', new ReflectionClass(ClassBluePrint::class)));
    }

    private function printClassName(string $className, ?ReflectionClass $declaringClass = null): ?string
    {
        return (new ClassNamePrinter())->resolveClassName($className, $declaringClass);
    }
}
