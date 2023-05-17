<?php

namespace Jerodev\DataMapper\Tests\Objects;

use Generator;
use Jerodev\DataMapper\Objects\DocBlockParser;
use PHPUnit\Framework\TestCase;

final class DocBlockParserTest extends TestCase
{
    /**
     * @test
     * @dataProvider docBlockDataProvider
     */
    public function it_should_parse_doc_blocks(string $input, array $paramTypes = [], ?string $returnType = null, ?string $varType = null): void
    {
        $doc = (new DocBlockParser())->parse($input);

        $this->assertEquals($paramTypes, $doc->paramTypes);
        $this->assertEquals($returnType, $doc->returnType);
        $this->assertEquals($varType, $doc->varType);
    }

    public static function docBlockDataProvider(): Generator
    {
        yield [
            <<<'DOC'
            /**
             * @param string $name  Name of the device
             * @param int $age      Age of the device
             * @return string       Years of warranty left
             */
            DOC,
            ['name' => 'string', 'age' => 'int'],
            'string',
            null,
        ];

        yield [
            <<<'DOC'
            /**
             * @var array<string, int | float> The length of each key
             */
            DOC,
            [],
            null,
            'array<string, int | float>',
        ];
    }
}
