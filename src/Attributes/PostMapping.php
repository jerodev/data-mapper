<?php

namespace Jerodev\DataMapper\Attributes;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_CLASS)]
class PostMapping
{
    public function __construct(
        public string|Closure $postMappingCallback
    ) {
    }
}
