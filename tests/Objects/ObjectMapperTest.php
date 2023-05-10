<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapperConfig;
use Jerodev\DataMapper\Objects\ObjectMapper;
use Jerodev\DataMapper\Tests\_Mocks\SuitEnum;
use Jerodev\DataMapper\Types\DataType;
use PHPUnit\Framework\TestCase;
use ValueError;

class ObjectMapperTest extends TestCase
{
    /** @test */
    public function it_should_throw_on_invalid_enum_value(): void
    {
        $this->expectException(ValueError::class);

        (new ObjectMapper(new Mapper()))->map(
            new DataType(SuitEnum::class, false),
            'x',
        );
    }

    /** @test */
    public function it_should_parse_enum_using_tryfrom_if_configured(): void
    {
        $config = new MapperConfig();
        $config->enumTryFrom = true;

        $value = (new ObjectMapper(new Mapper($config)))->map(
            new DataType(SuitEnum::class, true),
            'x',
        );

        $this->assertNull($value);
    }
}
