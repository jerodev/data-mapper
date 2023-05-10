<?php

namespace Jerodev\DataMapper\Attributes;

use Attribute;
use Closure;

/**
 * Execute a function right after the class has been mapped.
 * Can be either the name of a public function on the class, or a closure accepting the new object.
 * The function is gets the data array as a first parameter and the new object as the second.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class PostMapping
{
    public function __construct(
        public string|Closure $postMappingCallback
    ) {
    }
}
