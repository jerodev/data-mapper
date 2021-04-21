<?php

namespace Jerodev\DataMapper\Printers\ClassName;

use ReflectionClass;

class ParentNamespaceResolver implements ClassNameResolver
{
    public function getFullClassName(string $className, ?ReflectionClass $classReflection = null): ?string
    {
        if ($classReflection === null) {
            return null;
        }

        $rootClassName = '\\' . \ltrim($classReflection->getNamespaceName(), '\\') . '\\' . $className;
        if (\class_exists($rootClassName)) {
            return $rootClassName;
        }

        return null;
    }
}
