# Data Mapper
![run-tests](https://github.com/jerodev/data-mapper/workflows/run-tests/badge.svg)

> :warning: While the package currently works, it's still a work in progress, and things might break in future releases.

This package will map any raw data into a strong typed PHP object using a set of rules.

- [Basic mapping](#basic-mapping)
  - [Public properties](#public-properties)
  - [Property setters](#property-setters)
- [Custom mapping](#custom-mapping)
- [Syntax](#syntax)

## Basic mapping
Let's start with the basics. The mapper will map data directly to public properties on objects. If these properties have
types defined either using PHP7.4 property types or through PHPDOC, the mapper will attempt to cast the data to these 
types.

For example: imagine having an `Entity` class with the public properties `$id` and `$name`:

```php
class Entity
{
    public int $id;
    public string $name;
}
```

To map data from an array we simply pass the class name and an array with data to the mapper.

```php
$mapper = new \Jerodev\DataMapper\Mapper();
$entity = $mapper->map(Entity::class, [
    'id' => '5',
    'name' => 'foo',
]);

//    Entity {
//        +id: 5,
//        +name: "foo",
//    }
```

### Public properties
The easiest properties to map are public properties. The mapper will try to get the type for these properties using one 
of the following definitions:
1. PHP7.4 property type (also supports PHP8.0 union types)
2. PHPDoc type definition using [`@var`](https://manual.phpdoc.org/HTMLSmartyConverter/HandS/phpDocumentor/tutorial_tags.var.pkg.html)

The different definitions are checked in this order until a valid type is found.

If no valid type was found for a property, the provided data will be set to the property directly.

### Property setters
> :warning: Work in progress

## Custom mapping
Sometimes, classes have a constructor that cannot be mapped automatically. For these cases there is a 
[`MapsItself`](https://github.com/jerodev/data-mapper/blob/master/src/MapsItself.php) interface that defines one 
static function: `mapObject`.
When the mapper comes across a class that implements this interface, instead of using the constructor, the mapper will 
call the `MapsItself` with the provided data and is expected to return an instance of the current class.

## Syntax
PHPDoc currently has a few standards when it comes to defining property types. The plan is to support all official 
methods, but currently there are a few limitations:

### Arrays
For typed array types, only square brackets are currently supported:

```php
/** @var string[] */
private array $foo;
```
