<?php

namespace Jerodev\DataMapper\Tests\TestClasses;

class ArrayConstructorClass
{
    /** @var int[] */
    public array $integers;

    public function __construct(array $integers)
    {
        $this->integers = $integers;
    }
}
