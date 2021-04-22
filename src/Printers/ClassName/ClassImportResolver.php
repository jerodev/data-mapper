<?php

namespace Jerodev\DataMapper\Printers\ClassName;

use ReflectionClass;

class ClassImportResolver implements ClassNameResolver
{
    public function getFullClassName(string $className, ?ReflectionClass $classReflection = null): ?string
    {
        if ($classReflection === null) {
            return null;
        }

        $imports = $this->getImports($classReflection);
        foreach ($imports as $importClassName => $fqcn) {
            if ($importClassName === $className) {
                return $fqcn;
            }
        }

        return null;
    }

    private function getImports(ReflectionClass $classReflection): array
    {
        $resource = \fopen($classReflection->getFileName(), 'r');
        $imports = [];

        while ($line = \fgets($resource)) {
            if (\substr($line, 0, 4) === 'use ') {
                $parts = \explode(' ', \trim($line, "; \n"));

                // Skip imports for functions or constants.
                if ($parts[1] === 'function' || $parts[1] === 'const') {
                    continue;
                }

                $name = $parts[3] ?? null;
                if ($name === null) {
                    $fqcnParts = \explode('\\', $parts[1]);
                    $name = \end($fqcnParts);
                }

                $imports[$name] = $parts[1];
            }

            // When the class starts, no more imports are available.
            if (\substr($line, 0, 6) === 'class ' || \substr($line, 0, 12) === 'final class ') {
                break;
            }
        }

        return $imports;
    }
}
