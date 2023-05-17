<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;

class ClassResolver
{
    public function resolve(string $name, ?string $sourceFile = null): string
    {
        if (\class_exists($name)) {
            return $name;
        }

        // Find the file where this class is mentioned
        $sourceFile ??= $this->findSourceFile();
        if ($sourceFile === null) {
            throw new CouldNotResolveClassException($name);
        }

        $name = $this->findClassNameInFile($name, $sourceFile);
        if (\class_exists($name)) {
            return $name;
        }

        throw new CouldNotResolveClassException($name, $sourceFile);
    }

    private function findSourceFile(): ?string
    {
        $backtrace = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $mapperFileSuffix = \implode(\DIRECTORY_SEPARATOR, ['data-mapper', 'src', 'Mapper.php']);

        $foundMapper = false;
        foreach ($backtrace as $trace) {
            if (\str_ends_with($trace['file'], $mapperFileSuffix)) {
                $foundMapper = true;
                continue;
            }

            if ($foundMapper) {
                return $trace['file'];
            }
        }

        return null;
    }

    private function findClassNameInFile(string $name, string $sourceFile): string
    {
        $file = \file_get_contents($sourceFile);
        if ($file === false) {
            return $name;
        }

        $nameParts = \explode('\\', $name);
        $lastPart = \end($nameParts);

        $newline = false;
        $fileLength = \strlen($file);
        for ($i = 0; $i < $fileLength; $i++) {
            $char = $file[$i];

            // Don't care about spaces
            if ($char === ' ' || $char === "\t") {
                continue;
            }

            if ($char === \PHP_EOL) {
                $newline = true;
                continue;
            }

            // Find the class in the same namespace as the caller
            if ($newline && $char === 'n' && \substr($file, $i, 10) === 'namespace ') {
                $i += 10;
                $namespace = '';
                while (($char = $file[$i++]) !== ';') {
                    $namespace .= $char;
                }

                $classInNamespace = $namespace . '\\' . $lastPart;
                if (\class_exists($classInNamespace)) {
                    return $classInNamespace;
                }
            }

            // If we are after a newline and find a use statement, parse it!
            if ($newline && $char === 'u' && \substr($file, $i, 4) === 'use ') {
                $i += 4;

                $startAlias = false;
                $importName = '';
                $importAlias = '';
                while (($char = $file[$i++]) !== ';') {
                    if ($char === ' ' && \substr($file, $i, 3) === 'as ') {
                        $startAlias = true;
                        $i += 3;
                        continue;
                    }

                    if ($startAlias) {
                        $importAlias .= $char;
                    } else {
                        $importName .= $char;
                    }
                }

                $nameParts = \explode('\\', $importName);
                $lastImportPart = \end($nameParts);

                if ($importAlias === $lastPart || $lastImportPart === $lastPart) {
                    return $importName;
                }

                $i--;
            }

            $newline = false;
        }

        return $name;
    }
}
