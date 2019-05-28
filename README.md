# celestriode/json-utils

Provides a handful of utilities for simple interfacing with a JSON-derived object.

## Deserializing

If you do not already have an object, start by deserializing the string.

```php
use Celestriode\JsonUtils\JsonUtils;

$string = '{"key": "value"}';

$object = JsonUtils::deserialize($string);
```

## Getting values

There are a number of methods to return a given value within the object. They will throw a `JsonUtils\Exception\NotFound` exception if the key did not exist, or a `JsonUtils\Exception\PredicateFailed` exception if the key did exist but the datatype was incorrect.

```php
$value = JsonUtils::getBoolean('key_name', $object);
$value = JsonUtils::getString('key_name', $object);
$value = JsonUtils::getInteger('key_name', $object);
$value = JsonUtils::getDouble('key_name', $object);
$value = JsonUtils::getObject('key_name', $object);
$value = JsonUtils::getArray('key_name', $object);
```

## Checking key existence

You can check whether or not a key exists.

```php
$bool = JsonUtils::hasKey('key_name', $object);
```

Adding `true` will cause it to throw exceptions instead of returning a boolean. This can be paired with custom predicates to test the value against, if it exists. For example, the following will throw a `JsonUtils\Exception\PredicateFailed` exception if the test failed.

```php
JsonUtils::hasKey('key_name', $object, true, new class extends Predicate\Predicate {

    public function test($value) {

        return $value === "literal value";
    }
});
```

You may also specify a custom error message when choosing to throw exceptions.

```php
JsonUtils::hasKey('key_name', $object, true, new class extends Predicate\Predicate {

    protected $error = 'Value may only be positive';

    public function test($value) {

        return $value > 0;
    }
});
```

## Other functions

You can get an array of key values at the root of the object.

```php
$keys = JsonUtils::getKeys($object);
```

----

You can get a list of invalid keys by providing a list of valid keys. This is used for validating a JSON structure that a user has submitted while providing them with useful feedback.

```php
/* JSON object structure:
 {
     "valid_key": 1",
     "etc": 2,
     "not_valid": 3
 }
*/

$invalidKeys = JsonUtils::getInvalidKeys($object, 'valid_key', 'another_valid_key', 'this_key_valid', 'etc');
```

`$invalidKeys` will contain `["not_valid"]`.

----

You can run a supplied function for each nested object at the root of the supplied object. If any values at the root are not objects, they will simple be ignored.

```php
/* JSON object structure:
 {
     "ignored": "string",
     "obj1": {},
     "obj2": {}
 }
*/

JsonUtils::perObject($object, function($key, $nestedObject) {

    echo $key . '<br>';
});
```

Result:

> obj1

> obj2