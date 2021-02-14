<?php

namespace Jerodev\DataMapper\Tests\Models;

use Generator;
use Jerodev\DataMapper\Models\DataType;
use PHPUnit\Framework\TestCase;

final class DataTypeTest extends TestCase
{
    /** @test */
    public function it_should_parse_force_nullable(): void
    {
        $this->assertTrue(DataType::parse('int', true)->isNullable());
        $this->assertTrue(DataType::parse('?int', true)->isNullable());
        $this->assertFalse(DataType::parse('int', false)->isNullable());
        $this->assertTrue(DataType::parse('?int', false)->isNullable());
    }

    /** @test */
    public function it_should_parse_square_brackets_to_array_level(): void
    {
        $dataType = DataType::parse('int[][][]');

        $this->assertEquals('int', $dataType->getType());
        $this->assertTrue($dataType->isArray());
        $this->assertTrue($dataType->getArrayChildType()->isArray());
        $this->assertTrue($dataType->getArrayChildType()->getArrayChildType()->isArray());
        $this->assertFalse($dataType->getArrayChildType()->getArrayChildType()->getArrayChildType()->isArray());
    }

    /**
     * @test
     * @dataProvider parseTestProvider
     */
    public function it_should_parse_type_strings(string $input, string $type, bool $isArray, bool $isNullable): void
    {
        $dataType = DataType::parse($input);

        $this->assertEquals($type, $dataType->getType());
        $this->assertEquals($isArray, $dataType->isArray());
        $this->assertEquals($isNullable, $dataType->isNullable());
    }

    public function parseTestProvider(): Generator
    {
        yield ['int', 'int', false, false];
        yield ['array', 'array', false, false];
        yield ['int[]', 'int', true, false];
        yield ['?int', 'int', false, true];
        yield ['?int[]', 'int', true, true];
    }
}
