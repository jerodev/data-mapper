<?php

namespace Jerodev\DataMapper\Objects;

use Jerodev\DataMapper\Attributes\PostMapping;
use Jerodev\DataMapper\Exceptions\CouldNotResolveClassException;
use Jerodev\DataMapper\Mapper;
use Jerodev\DataMapper\MapsItself;
use Jerodev\DataMapper\Types\DataType;
use Jerodev\DataMapper\Types\DataTypeCollection;
use Jerodev\DataMapper\Types\DataTypeFactory;
use mysql_xdevapi\Statement;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;

class ObjectMapper
{
    private const MAPPER_FUNCTION_PREFIX = 'jmapper_';

    private readonly ClassBluePrinter $classBluePrinter;
    private readonly Standard $prettyPrinter;

    public function __construct(
        private readonly Mapper $mapper,
        private readonly DataTypeFactory $dataTypeFactory = new DataTypeFactory(),
    ) {
        $this->classBluePrinter = new ClassBluePrinter();
        $this->prettyPrinter = new Standard();
    }

    /**
     * @param DataType|string $type
     * @param array|string $data
     * @return object|null
     * @throws CouldNotResolveClassException
     */
    public function map(DataType|string $type, array|string $data): ?object
    {
        $class = $this->dataTypeFactory->classResolver->resolve(\is_string($type) ? $type : $type->type);
        if (\is_subclass_of($class, MapsItself::class)) {
            return \call_user_func([$class, 'mapSelf'], $data, $this->mapper);
        }

        // If the data is a string and the class is an enum, create the enum.
        if (\is_string($data) && \is_subclass_of($class, \BackedEnum::class)) {
            if ($this->mapper->config->enumTryFrom) {
                return $class::tryFrom($data);
            }

            return $class::from($data);
        }

        $functionName = self::MAPPER_FUNCTION_PREFIX . \md5($class . ($type instanceof DataType && $type->isNullable ? '1' : '0'));
        if ($this->mapper->config->classCacheKeySource === 'md5' || $this->mapper->config->classCacheKeySource === 'modified') {
            $reflection = new ReflectionClass($class);
            $functionName = match ($this->mapper->config->classCacheKeySource) {
                'md5' => self::MAPPER_FUNCTION_PREFIX . \md5(\md5_file($reflection->getFileName()) . $functionName),
                'modified' => self::MAPPER_FUNCTION_PREFIX . \md5(\filemtime($reflection->getFileName()) . $functionName),
            };
        }

        $fileName = $this->mapperDirectory() . \DIRECTORY_SEPARATOR . $functionName . '.php';
        if (! \file_exists($fileName)) {
            \file_put_contents(
                $fileName,
                $this->createObjectMappingFunction(
                    $this->classBluePrinter->print($class),
                    $functionName,
                    $type instanceof DataType && $type->isNullable,
                ),
            );
        }

        // Include the function containing file and call the function.
        require_once($fileName);
        return ($functionName)($this->mapper, $data);
    }

    public function clearCache(): void
    {
        foreach (\glob($this->mapperDirectory() . \DIRECTORY_SEPARATOR . self::MAPPER_FUNCTION_PREFIX . '*.php') as $file) {
            \unlink($file);
        }
    }

    public function mapperDirectory(): string
    {
        $dir = \str_replace('{$TMP}', \sys_get_temp_dir(), $this->mapper->config->classMapperDirectory);
        if (! \file_exists($dir) && ! \mkdir($dir, 0777, true) && ! \is_dir($dir)) {
            throw new \RuntimeException("Could not create caching directory '{$dir}'");
        }

        return \rtrim($dir, \DIRECTORY_SEPARATOR);
    }

    private function createObjectMappingFunction(ClassBluePrint $blueprint, string $mapFunctionName, bool $isNullable): string
    {
        /**
         * This array will contain all statements that will be part of the function.
         * @var array<Stmt> $ast
         */
        $ast = [];

        if ($isNullable) {
            $ast[] = new Stmt\If_(
                new BooleanAnd(
                    new Identical(
                        new Variable('data'),
                        new Array_(attributes: ['kind' => Array_::KIND_SHORT]),
                    ),
                    new PropertyFetch(
                        new PropertyFetch(
                            new Variable('mapper'),
                            'config',
                        ),
                        'nullObjectFromEmptyArray',
                    ),
                ),
                [
                    'stmts' => [
                        new Stmt\Return_(new ConstFetch(new Name('null'))),
                    ],
                ],
            );
        }

        // Instantiate a new object
        $args = [];
        foreach ($blueprint->constructorArguments as $name => $argument) {
            $arg = new ArrayDimFetch(
                new Variable('data'),
                new String_($name),
            );
            if ($argument['type']->isNullable()) {
                $arg = new Coalesce(
                    $arg,
                    new ConstFetch(new Name('null')),
                );
            }

            if ($argument['type'] !== null) {
                $arg = $this->castInMapperFunction($arg, $argument['type'], $blueprint);
            }

            if (\array_key_exists('default', $argument)) {
                $arg = $this->wrapDefault($arg, $name, $argument['default']);
            }

            $args[] = $arg;
        }
        $ast[] = new Stmt\Expression(
            new Assign(
                new Variable('x'),
                new New_(
                    new Name($blueprint->namespacedClassName),
                    $args,
                ),
            ),
        );

        // Map properties
        foreach ($blueprint->properties as $name => $property) {
            // Use a foreach to map key/value arrays
            if (\count($property['type']->types) === 1 && $property['type']->types[0]->isArray() && \count($property['type']->types[0]->genericTypes) === 2) {
                $ast = \array_merge($ast, $this->buildPropertyForeachMapping($name, $property, $blueprint));

                continue;
            }

            $value = new ArrayDimFetch(
                new Variable('data'),
                new String_($name),
            );
            if ($property['type']->isNullable()) {
                $value = new Coalesce(
                    $value,
                    new ConstFetch(new Name('null')),
                );
            }

            $value = $this->castInMapperFunction($value, $property['type'], $blueprint);
            if (\array_key_exists('default', $property)) {
                $value = $this->wrapDefault($value, $name, $property['default']);
            }

            $value = new Assign(
                new PropertyFetch(
                    new Variable('x'),
                    $name,
                ),
                $value,
            );

            if ($this->mapper->config->allowUninitializedFields && ! \array_key_exists('default', $property)) {
                $value = $this->wrapArrayKeyExists($value, $name);
            }

            if ($value instanceof Expr) {
                $ast[] = new Stmt\Expression($value);
            } else {
                $ast[] = $value;
            }
        }

        // Post mapping functions?
        foreach ($blueprint->classAttributes as $attribute) {
            if ($attribute instanceof PostMapping) {
                if (\is_string($attribute->postMappingCallback)) {
                    $ast[] = new Stmt\Expression(
                        new Expr\MethodCall(
                            new Variable('x'),
                            $attribute->postMappingCallback,
                            [
                                new Arg(new Variable('data')),
                                new Arg(new Variable('x')),
                            ],
                        ),
                    );
                } else {
                    $ast[] = new Stmt\Expression(
                        new FuncCall(
                            new Name\FullyQualified('call_user_func'),
                            [
                                new Arg(new ConstFetch(new Name($attribute->postMappingCallback))),
                                new Arg(new Variable('data')),
                                new Arg(new Variable('x')),
                            ],
                        ),
                    );
                }
            }
        }

        // Return the result!
        $ast[] = new Stmt\Return_(new Variable('x'));

        // Make sure not to define the function twice
        $tree = new Stmt\If_(
            new Expr\BooleanNot(
                new FuncCall(
                    new Name\FullyQualified('function_exists'),
                    [
                        new Arg(new String_($mapFunctionName)),
                    ],
                ),
            ),
            [
                'stmts' => [
                    new Stmt\Function_(
                        new Identifier($mapFunctionName),
                        [
                            'params' => [
                                new Param(new Variable('mapper'), null, new Name\FullyQualified(Mapper::class)),
                                new Param(new Variable('data')),
                            ],
                            'stmts' => $ast,
                        ],
                    ),
                ],
            ],
        );

        return '<?php ' . PHP_EOL . PHP_EOL . $this->prettyPrinter->prettyPrint([$tree]);
    }

    private function castInMapperFunction(Expr $value, DataTypeCollection $type, ClassBluePrint $bluePrint): Expr
    {
        if (\count($type->types) === 1) {
            $type = $type->types[0];

            if ($type->isNullable) {
                return new Ternary(
                    new Identical(
                        $value,
                        new ConstFetch(new Name('null')),
                    ),
                    new ConstFetch(new Name('null')),
                    $this->castInMapperFunction($value, new DataTypeCollection([$type->removeNullable()]), $bluePrint),
                );
            }

            if ($type->isNative()) {
                return match ($type->type) {
                    'null' => new ConstFetch(new Name('null')),
                    'bool' => new FuncCall(new Name\FullyQualified('filter_var'), [$value, new ConstFetch(new Name\FullyQualified('FILTER_VALIDATE_BOOL'))]),
                    'float' => new Expr\Cast\Double($value, ['kind' => Expr\Cast\Double::KIND_FLOAT]),
                    'int' => new Expr\Cast\Int_($value),
                    'string' => new Expr\Cast\String_($value),
                    'object' => new Expr\Cast\Object_($value),
                    default => $value,
                };
            }

            if ($type->isArray()) {
                if ($type->isGenericArray()) {
                    return new Expr\Cast\Array_($value);
                }
                if (\count($type->genericTypes) === 1) {
                    $uniqid = \uniqid('x');
                    return new FuncCall(
                        new Name\FullyQualified('array_map'),
                        [
                            new Arg(
                                new Expr\ArrowFunction(
                                    [
                                        'params' => [
                                            new Param(new Variable($uniqid)),
                                        ],
                                        'expr' => $this->castInMapperFunction(new Variable($uniqid), $type->genericTypes[0], $bluePrint),
                                    ],
                                ),
                            ),
                            new Arg($value),
                        ],
                    );
                }
            }

            if (\is_subclass_of($type->type, \BackedEnum::class)) {
                $enumFunction = $this->mapper->config->enumTryFrom ? 'tryFrom' : 'from';

                return new Expr\StaticCall(
                    new Name($type->type),
                    $enumFunction,
                    [
                        new Arg($value),
                    ],
                );
            }

            if (\is_subclass_of($type->type, MapsItself::class)) {
                return new Expr\StaticCall(
                    new Name($type->type),
                    'mapSelf',
                    [
                        new Arg($value),
                        new Variable('mapper'),
                    ],
                );
            }

            $className = $this->dataTypeFactory->print($type, $bluePrint->fileName);
            if (\class_exists($className)) {
                return new Expr\MethodCall(
                    new Expr\PropertyFetch(
                        new Variable('mapper'),
                        'objectMapper',
                    ),
                    'map',
                    [
                        new Arg(new String_($className)),
                        new Arg($value),
                    ],
                );
            }
        }

        return new Expr\MethodCall(
            new Variable('mapper'),
            'map',
            [
                new Arg(new String_($this->dataTypeFactory->print($type, $bluePrint->fileName))),
                new Arg($value),
            ],
        );
    }

    private function wrapDefault(Expr $value, string $arrayKey, mixed $defaultValue): Expr
    {
        if (\is_object($defaultValue)) {
            $defaultRaw = new New_(new Name($defaultValue::class));
        } else {
            $defaultRaw = new ConstFetch(new Name(\var_export($defaultValue, true)));
        }

        return new Ternary(
            new FuncCall(
                new Name\FullyQualified('array_key_exists'),
                [
                    new Arg(new String_($arrayKey)),
                    new Arg(new Variable('data')),
                ],
            ),
            $value,
            $defaultRaw,
        );
    }

    /** @param Node|array<Node> $expression */
    private function wrapArrayKeyExists(Node|array $expression, string $arrayKey): Stmt\If_
    {
        return new Stmt\If_(
            new FuncCall(
                new Name\FullyQualified('array_key_exists'),
                [
                    new Arg(new String_($arrayKey)),
                    new Arg(new Variable('data')),
                ],
            ),
            [
                'stmts' => \is_array($expression)
                    ? $expression
                    : [
                        $expression instanceof Expr ? new Stmt\Expression($expression) : $expression,
                    ],
            ],
        );
    }

    public function __destruct()
    {
        if ($this->mapper->config->debug) {
            $this->clearCache();
        }
    }

    /**
     * @param array{type: DataTypeCollection, default?: mixed} $property
     * @return array<Stmt>
     */
    private function buildPropertyForeachMapping(string $propertyName, array $property, ClassBluePrint $blueprint): array
    {
        $ast = [];
        $ast[] = new Stmt\Expression(
            new Assign(
                new PropertyFetch(
                    new Variable('x'),
                    $propertyName,
                ),
                new Array_(attributes: ['kind' => Array_::KIND_SHORT]),
            ),
        );

        $ast[] = new Stmt\Foreach_(
            new ArrayDimFetch(
                new Variable('data'),
                new String_($propertyName),
            ),
            new Variable('value'),
            [
                'keyVar' => new Variable('key'),
                'stmts' => [
                    new Stmt\Expression(
                        new Assign(
                            new ArrayDimFetch(
                                new PropertyFetch(
                                    new Variable('x'),
                                    $propertyName,
                                ),
                                $this->castInMapperFunction(new Variable('key'), $property['type']->types[0]->genericTypes[0], $blueprint),
                            ),
                            $this->castInMapperFunction(new Variable('value'), $property['type']->types[0]->genericTypes[1], $blueprint),
                        ),
                    ),
                ],
            ],
        );

        if (\array_key_exists('default', $property) || $this->mapper->config->allowUninitializedFields) {
            $if = $this->wrapArrayKeyExists($ast, $propertyName);

            if (\array_key_exists('default', $property)) {
                $if->else = new Stmt\Else_([
                    new Stmt\Expression(
                        new Assign(
                            new PropertyFetch(
                                new Variable('x'),
                                $propertyName,
                            ),
                            new ConstFetch(new Name(\var_export($property['default'], true))),
                        ),
                    ),
                ]);
            }

            $ast = [$if];
        }

        return $ast;
    }
}
