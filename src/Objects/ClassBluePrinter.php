<?php

namespace Jerodev\DataMapper\Objects;

use ReflectionClass;

class ClassBluePrinter
{
    public function print(string $class): ClassBluePrint
    {
        $reflection = new ReflectionClass($class);

        $blueprint = new ClassBluePrint();
        $this->printConstructor($reflection, $blueprint);
        // TODO: map public properties

        return $blueprint;
    }

    private function printConstructor(ReflectionClass $reflection, ClassBluePrint $bluePrint): void
    {
        $constructor = $reflection->getConstructor();
        if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
            return;
        }

        // TODO: parse doc block to get more information about argument types
        foreach ($constructor->getParameters() as $param) {
            $bluePrint->constructorArguments[] = \array_filter([
                'name' => $param->getName(),
                'type' => $param->getType()->getName(),
                'default' => $param->getDefaultValue(),
            ]);
        }
    }
}
