# Data Mapper
![run-tests](https://github.com/jerodev/data-mapper/workflows/run-tests/badge.svg)

> :warning: While the package currently works, it's still a work in progress, and things might break in future releases.

This package will map any raw data into a strong typed PHP object.

- [Installation](#installation)
- [Basic mapping](#basic-mapping)
  - [Typing properties](#typing-properties)
  - [Custom mapping](#custom-mapping)
- [Configuration](#configuration)
- [Under the hood](#under-the-hood)

## Installation
The mapper has no external dependencies apart from PHP8.1 or higher. It can be installed using composer:

```bash
composer require jerodev/data-mapper
```

## Basic mapping
Let's start with the basics. The mapper will map data directly to public properties on objects. If these properties have
types defined either using types introduced in PHP7.4 or through [PHPDoc](https://phpstan.org/writing-php-code/phpdoc-types), the mapper will attempt to cast the data to these
types.

For example: imagine having an `Entity` class with the public properties `$id` and `$name`:

```php
class User
{
    public int $id;
    public string $name;
}
```

To map data from an array we simply pass the class name and an array with data to the mapper.

```php
$mapper = new \Jerodev\DataMapper\Mapper();
$entity = $mapper->map(User::class, [
    'id' => '5',
    'name' => 'John Doe',
]);

//    User {
//        +id: 5,
//        +name: "John Doe",
//    }
```

This is a simple example, but the mapper can also map nested objects, arrays of objects, keyed arrays, and even multi-level arrays.

### Typing properties
The type of the properties is checked from two places:
1. The type of the property itself. This can be defined using typehints [introduced in PHP7.4](https://wiki.php.net/rfc/typed_properties_v2);
2. [PHPDoc types](https://phpstan.org/writing-php-code/phpdoc-types) for properties and constructor parameters.

First the native type of the property is checked, if this is defined and can be mapped the type will be used.
If no type is provided or the type is a generic array, the mapper will check the PHPDoc for type of the property.

When a property is typed using a [union type](https://wiki.php.net/rfc/union_types_v2), the mapper will try to map any
of the provided types from first to last until one mapping succeeds. The only exception is that `null` is always tried
last.

If no valid type was found for a property, the provided data will be set to the property directly without any
conversion.

## Custom mapping
Sometimes, classes have a constructor that cannot be mapped automatically. For these cases there is a
[`MapsItself`](https://github.com/jerodev/data-mapper/blob/master/src/MapsItself.php) interface that defines one
static function: `mapObject`.
When the mapper comes across a class that implements this interface, instead of using the constructor, the mapper will
call the `MapsItself` with the provided data and is expected to return an instance of the current class.

## Configuration
The mapper comes with a few configuration options that can be set using the [`MapperConfig`](https://github.com/jerodev/data-mapper/blob/master/src/MapperConfig.php)
object and passed to the mappers' constructor. This is not required, if no configuration is passed, the default config
is used.

| Option                 | Type     | Default        | Description                                                                                                                                                                |
|------------------------|----------|----------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `classMapperDirectory` | `string` | `/tmp/mappers` | This is the location the mapper will create cached mapper functions for objects.<br />The default location is a mappers function in the operating system temporary folder. |
| `debug`                | `bool`   | `false`        | Enabling debug will clear all cached mapper functions after mapping has completed.                                                                                         |
| `enumTryFrom`          | `bool`   | `false`        | Enabling this will use the `::tryFrom()` method instead of `::from()` to parse strings to enums.                                                                           |
| `strictNullMapping`    | `bool`   | `true`         | If enabled, the mapper will throw an error when a `null` value is passed for a property that was not typed as nullable.                                                    |

## Under the hood
For simple native types, the mapper will use casting to convert the data to the correct type.

When requesting an array type, the mapper will call itself with the type of the array elements for each of the elements in the
array.

For object types, some magic happens. On the very first run for a certain class, the mapper will use reflection to
gather information about the class and build a mapper function based on the properties of the class.
The function will also take into account required and optional properties that are passed to the constructor.

The goal is to have as much and as simple mapping as possible in these generated functions without having to go back
to the mapper, to reach the best performance.

As an example, this is one of the testing classes of this library and its generated mapper function:

<table>
<tr>
<td>
```php
#[PostMapping('post')]
class UserDto
{
    /** First name and last name */
    public string $name;

    /** @var array<self> */
    public array $friends = [];
    public ?SuitEnum $favoriteSuit = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function post(): void
    {
        $this->name = \ucfirst($this->name);
    }
}
```
</td>
<td>
```php
function jmapper_8cf8f45dc33c7f58ab728699ac3ebec3(Jerodev\DataMapper\Mapper $mapper, array $data) {
    $x = new Jerodev\DataMapper\Tests\_Mocks\UserDto((string) $data['name']);
    $x->name = (string) $data['name'];
    $x->friends = (\array_key_exists('friends', $data) ? \array_map(static fn ($x6462755ab00b1) => $mapper->map('Jerodev\DataMapper\Tests\_Mocks\UserDto', $x6462755ab00b1), $data['friends']) : []);
    $x->favoriteSuit = (\array_key_exists('favoriteSuit', $data) ? Jerodev\DataMapper\Tests\_Mocks\SuitEnum::from($data['favoriteSuit']) : NULL);

    $x->post($data, $x);

    return $x;
}
```
</td>
</tr>
</table>

