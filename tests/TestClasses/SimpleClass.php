<?php

namespace Jerodev\DataMapper\Tests\TestClasses;

class SimpleClass
{
    public string $foo;
    public int $number;
    public float $floatingPoint;
    public bool $checkbox;

    /** @var int|string */
    public $numberOrString;

    /** @var int[][] */
    public array $numbers;
}
