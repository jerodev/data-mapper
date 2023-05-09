<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;

class ClassResolver
{
    public function resolve(string $name): string
    {
        if (\class_exists($name)) {
            return $name;
        }

        // Find the file where this class is mentioned
        $sourceFile = $this->findSourceFile();
        if ($sourceFile === null) {
            throw new CouldNotResolveClassException($name);
        }

        // TODO: attempt to resolve class name through use statements in file

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
}
