# Data Mapper
![run-tests](https://github.com/jerodev/data-mapper/workflows/run-tests/badge.svg) [![Latest Stable Version](http://poser.pugx.org/jerodev/data-mapper/v)](https://packagist.org/packages/jerodev/data-mapper) 

This package will map any raw data into a predefined strong-typed PHP object.

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

## Documentation
More information about mapping, configuration and best practices can be found in [the documentation](https://docs.deviaene.eu/data-mapper/).

## License
This library is licensed under the MIT License (MIT). Please see [License File](license.md) for more information.
