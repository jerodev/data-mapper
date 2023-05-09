<?php

namespace Jerodev\DataMapper\Objects;

class DocBlock
{
    /** @var array<string, string> */
    public array $paramTypes = [];
    public ?string $returnType = null;
    public ?string $varType = null;

    public function getParamType(string $name): ?string
    {
        return $this->paramTypes[$name] ?? null;
    }
}
