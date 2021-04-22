<?php

namespace Jerodev\DataMapper\Printers\ClassName;

use ReflectionClass;

class RootNamespaceResolver implements ClassNameResolver
{
    public function getFullClassName(string $className, ?ReflectionClass $classReflection = null): ?string
    {
        $rootClassName = "\\{$className}";
        if (\class_exists($rootClassName)) {
            return $rootClassName;
        }

        return null;
    }
}
