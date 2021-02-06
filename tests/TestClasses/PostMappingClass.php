<?php

namespace Jerodev\DataMapper\Tests\TestClasses;

use Jerodev\DataMapper\Attributes\PostMapping;

#[PostMapping('postMapping')]
class PostMappingClass
{
    public int $value;

    public function postMapping(): void
    {
        $this->value *= 2;
    }
}