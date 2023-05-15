# Class mapper function
When needing to map a class, the mapper will use reflection to create a mapper function for that class that can be
reused on subsequent calls.  
To get the best performance out of these mapper functions, we attempt to map as many properties as possible directly in
the mapper function.

Here we will go through the different types of properties that will be mapped directly in the mapper function.

### Simple mappings
For simple mappings to native types is done through direct casting.

```php
public int $score;

// Will be mapped as:

$x->score = (int) $data['score'];
```

If the value is not a native type, the function will call the mapper.

```php
public UserDto $user;

// Will be mapped as:

$x->user = $mapper->map('\Jerodev\DataMapper\Tests\_Mocks\UserDto', $data['user']);
```

### Default values
When a default value is provided for a property, the property is seen as optional. The mapper will use that default
value if the property is not provided in the data array.

```php
public int $score = 10;

// Will be mapped as:

$x->score = (\array_key_exists('score', $data) ? (int) $data['score'] : 10);
```

### Enums
Enums are mapped using the `::from()` method of the enum class. If the mapper [is configured to use `::tryFrom()`](../readme.md#configuration),
this method will be used instead.

```php
public SuitEnum $cardType;

// Will be mapped as:

$x->cardType = \Jerodev\DataMapper\Tests\_Mocks\SuitEnum::from($data['cardType']);
```

### Arrays with value type defined
Arrays with a value type defined will use the [`array_map`](https://www.php.net/manual/en/function.array-map.php)
function in combination with simple mappings to map all values in the data array.

```php
/** @var array<string> */
public array $usernames;

// Will be mapped as:

$x->usernames = \array_map(static fn ($xyz) => (string) $xyz, $data['usernames']);
```

### Arrays with key and value type defined
When an array has both its key and value type defined, a foreach is used to map the values to the property.

```php
/** @var array<string, int> */
public array $userScores;

// Will be mapped as:

$x->userScores = [];
foreach ($data['userScores'] as $key => $value) {
    $x->userScores[(string) $key] = (int) $value;
}
```


