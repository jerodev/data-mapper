# Data Mapper
![run-tests](https://github.com/jerodev/data-mapper/workflows/run-tests/badge.svg)

> :warning: While the package currently works, it's still a work in progress, and things might break in future releases.

This package will map any raw data into a strong typed PHP object.

- [Basic mapping](#basic-mapping)
  - [Typing properties](#typing-properties)
  - [Custom mapping](#custom-mapping)

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
of the provided types from first to last until one mapping succeeds. The only exception is that `null` is always tried last.

If no valid type was found for a property, the provided data will be set to the property directly without any conversion.

## Custom mapping
Sometimes, classes have a constructor that cannot be mapped automatically. For these cases there is a
[`MapsItself`](https://github.com/jerodev/data-mapper/blob/master/src/MapsItself.php) interface that defines one
static function: `mapObject`.
When the mapper comes across a class that implements this interface, instead of using the constructor, the mapper will
call the `MapsItself` with the provided data and is expected to return an instance of the current class.
