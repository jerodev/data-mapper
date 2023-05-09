<?php

namespace Jerodev\DataMapper\Objects;

class DocBlockParser
{
    public function parse(string $contents): DocBlock
    {
        $docblock = new DocBlock();

        for ($i = 0; $i < \strlen($contents); $i++) {
            $char = $contents[$i];

            if ($char === '@') {
                $identifier = '';
                while (($char = $contents[$i++]) !== ' ') {
                    $identifier .= $char;
                }

                match ($identifier) {
                    '@param' => $this->parseParam($contents, $i, $docblock),
                    '@return', '@var' => $this->parseReturn($contents, $i, $docblock, \substr($identifier, 1) . 'Type'),
                    default => null,
                };
            }
        }

        return $docblock;
    }

    private function parseParam(string $contents, int &$i, DocBlock $docblock): void
    {
        $type = $this->parseType($contents, $i);

        $docblock->paramTypes[$this->parseVariable($contents, $i)] = $type;
    }

    private function parseReturn(string $contents, int &$i, DocBlock $docblock, string $docProperty = 'returnType'): void
    {
        $docblock->{$docProperty} = $this->parseType($contents, $i);
    }

    private function parseType(string $contents, int &$i): string
    {
        $type = '';
        $sawSpace = false;
        $lastChar = '';
        for (; $i < \strlen($contents); $i++) {
            $char = $contents[$i];
            if ($char === ' ') {
                $type .= $char;
                $sawSpace = true;
                continue;
            }

            if (\in_array($char, ['|', ','])) {
                $type .= $char;
                $lastChar = $char;
                $sawSpace = false;
                continue;
            }

            if ($sawSpace && ! \in_array($lastChar, ['|', ','])) {
                break;
            }

            $type .= $char;
            $lastChar = $char;
            $sawSpace = false;
        }

        return \trim($type);
    }

    private function parseVariable(string $contents, int &$i): string
    {
        $variable = '';
        for (; $i < \strlen($contents); $i++) {
            $char = $contents[$i];
            if ($char === ' ') {
                if (empty($variable)) {
                    continue;
                }

                break;
            }

            if (empty($variable) && $char !== '$') {
                throw new \Exception('Expected variable to start with \'$\', found \'' . $char . '\'.');
            }

            $variable .= $char;
        }

        return \trim(\substr($variable, 1));
    }
}
