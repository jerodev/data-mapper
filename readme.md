# Data Mapper
![run-tests](https://github.com/jerodev/data-mapper/workflows/run-tests/badge.svg)

This package will map any raw data into a strong typed PHP object using a set of rules.

- [Basic mapping](#basic-mapping)

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