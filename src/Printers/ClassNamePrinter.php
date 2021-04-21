<?php

namespace Jerodev\DataMapper\Printers;

use Jerodev\DataMapper\Printers\ClassName\ClassNameResolver;
use ReflectionClass;

/**
 * This class takes a class name and tries to convert it to a fully qualified class name with namespace.
 */
class ClassNamePrinter
{
    /** @var ClassNameResolver[] */
    private array $classNameResolvers;

    public function __construct(?array $classNameResolvers = null)
    {
        if ($classNameResolvers !== null) {
            $this->classNameResolvers = $classNameResolvers;
            return;
        }

        $this->classNameResolvers = [
            new ClassName\RootNamespaceResolver(),
            new ClassName\ParentNamespaceResolver(),
            new ClassName\ClassImportResolver(),
        ];
    }

    public function resolveClassName(string $className, ?ReflectionClass $declaringClass = null): ?string
    {
        foreach ($this->classNameResolvers as $classNameResolver) {
            $resolvedClassName = $classNameResolver->getFullClassName($className, $declaringClass);
            if ($resolvedClassName !== null) {
                return $resolvedClassName;
            }
        }

        $parentParent = $declaringClass->getParentClass();
        if ($parentParent instanceof ReflectionClass && $parentParent->isUserDefined()) {
            return $this->resolveClassName($className, $parentParent);
        }

        return null;
    }
}