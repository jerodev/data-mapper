<?php

namespace Jerodev\DataMapper\Printers\ClassName;

use ReflectionClass;

interface ClassNameResolver
{
    public function getFullClassName(string $className, ?ReflectionClass $classReflection = null): ?string;
}
