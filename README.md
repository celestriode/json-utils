# celestriode/json-utils

Provides a handful of utilities for simple interfacing with a JSON-derived object.

# General utilities

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

# Structure validation

A JSON input can be compared to an expected structure while providing feedback in the form of reports.

An example of a simple structure:

```php
$json = '{
    "test": "hello",
    "integer": 5.0,
    "numeric": 7.0
}';

$structure = Structure::root(
    Structure::string('test'),
    Structure::integer('integer'),
    Structure::number('numeric')
);

$reports = $structure->compare(JsonUtils::deserialize($json));
```

The reports will contain an error stating that `integer` is a double instead of an integer.

## Structures

All structures are required by default. Set `$required` to false to have optional structures.

### Base structures

| Structure | Description
| - | - |
| `Structure::any(string $key, bool $required)` | Checks for any structure. |
| `Structure::boolean(string $key, bool $required)` | Checks for a boolean. |
| `Structure::integer(string $key, bool $required)` | Checks for an integer. |
| `Structure::double(string $key, bool $required)` | Checks for a double. |
| `Structure::number(string $key, bool $required)` | Checks for both an integer and a double. |
| `Structure::string(string $key, bool $required)` | Checks for a string. |
| `Structure::array(string $key, bool $required)` | Checks for an array. |
| `Structure::object(string $key, bool $required, Structure ...$children)` | Checks for an object with optional children. |

### Special structures

| Structure | Description
| - | - |
| `Structure::root(Structure ...$children)` | The beginning of a structure. Use this when starting a structure. |
| `Structure::empty()` | A structure that has no key, value, or children to verify. Primarily used with branches and arrays. |
| `Structure::placeholder(int $datatype, bool $required, Structure ...$children)` | Matches any key, as long as it has the provided data type. |

## Optional fields

Passing `false` to the structure will allow for optional fields.

```php
$json = '{
    "required_field": "hello",
    "another_required_field": "hi",
    "optional_field": "goodbye"
}';

$structure = Structure::root(
    Structure::string('required_field'),
    Structure::string('another_required_field', true),
    Structure::integer('optional_field', false)
);
```

## Objects

An object can accept children to verify the structure at a lower depth.

```php
$json = '{
    "the_object": {
        "something": 5,
        "another": "test",
        "nested": {
            "correct": true
        }
    }
}';

$structure = Structure::root(
    Structure::object('the_object', true,
        Structure::number('something'),
        Structure::string('another'),
        Structure::object('nested', true,
            Structure::boolean('correct')
        )
    )
);
```

## Placeholders

A placeholder will accept any key value. The datatype must match to be used for the placeholder.

```php
$json = '{
    "the_object": {
        "something": "hello",
        "another": "test",
        "there": "goodbye"
    }
}';

$structure = Structure::root(
    Structure::object('the_object', true,
        Structure::placeholder(JsonUtils::STRING)
    )
);
```

## Conditions

A condition can be applied to a structure which will aid in determining errors. For example, the `HasValue()` condition checks if the current structure's value is within a list of verified values.

```php
$json = '{
    "data": "helo"
}';

$condition = new Conditions\HasValue('hello', 'goodbye');

$structure = Structure::root(
    Structure::string('data')->addCondition($condition)
);
```

The report will contain a warning about how "helo" is not a valid input for that field.

### List of packaged conditions

| Condition | Description
| - | - |
| `HasValue(string ...$values)` | Succeeds if the current structure's value is one of the supplied values. |
| `KeyHasValue(string $key, string ...$values)` | Succeeds if the specified key, which must be a sibling of the current structure, has the specified values. This is primarily used for branching.
| `AtLeastOneKey()` | Succeeds if the object has at least 1 field with any key name. |
| `AtLeastOneValidKey()` | Succeeds if the object contains at least 1 field with any valid key specified in the structure itself. |
| `KeyIsType(int $datatype, string $key)` | Succeeds if the field is of the specified datatype. This is primarily used for arrays.
| `CannotHaveValue(string ...$values)` | Fails if the current structure's value is any one of the specified values. |
| `ExclusiveKey(bool $oneKeyRequired, string ...$keys)` | Fails if any of the specified keys co-exist as siblings in the current structure. if `$oneKeyRequired` is true, then one of the specified keys must also exist. |
| `FailWithMessage(string $message)` | Always fails and will add a fatal warning with the supplied error message. |
| `KeysExist(string ...$keys)` | Succeeds as long as all supplied keys exist as siblings to the current structure, regardless of their value. |
| `WithinRange(float $min = null, float $max = null)` | Succeeds provided the current structure's value is numeric (strings included) and is between the specified min and max. If either are null, no limit. If both are null, simply returns true as long as the value is numeric (strings included).

### Custom conditions

Conditions must implement `Conditions\ICondition`, which has one required method:

```php
public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
```

- `$json` will be the JSON input, matching the current depth of the structure, allowing you to check other data at this depth.
- `$structure` is the expected structure, and will notably contain the key of the expected structure.
- `$reports` will hold any errors you give it should something fail in the condition.
- `$announce` will be false if errors should not be reported.

For example, the following checks if the object contains a `min` and `max` keys and ensures that the minimum is not higher than the maximum.

```php
$json = '{
    "range": {
        "min": 5,
        "max": 7
    }
}';

$condition = new class implements Conditions\ICondition {

    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        try {

            $min = JsonUtils::getInteger('min', $json);
            $max = JsonUtils::getInteger('max', $json);

            // Check if min is higher than max.

            if ($min > $max) {

                // Add fatal error if allowed.

                if ($announce) {

                    $reports->addFatal('Minimum cannot be higher than maximum.');
                }

                // Condition failed, return false.

                return false;
            }
        } catch (Exception\JsonException $e) {

            // Add fatal error if allowed.

            if ($announce) {

                $reports->addFatal($e->getMessage());
            }
        }

        // Nothing went wrong, return true.

        return true;
    }
};

$structure = Structure::root(
    Structure::object('range', true,
        Structure::integer('min'),
        Structure::integer('max')
    )->addCondition($condition)
);

$report = $structure->compare(JsonUtils::deserialize($json));
```

The report will contain an error about how the value of `min` is higher than the value of `max`.

## Branching

Branching allows you to have a dynamic structure based on certain provided conditions. Use the `addBranch()` method on any structure to do so:

```php
addBranch(string $friendlyName, Structure $structure, Conditions\ICondition ...$conditions)
```

**Note: conditions used for branching should not report errors.** Otherwise if a branch succeeds while the others fail, you will receive false-positives in the report. Make sure to check if `$announce` is true when creating custom conditions.

For example, the following will expect a different structure depending on the value of `planet`.

```php
$json = '{
    "planet": "earth",
    "data": {
        "humans": 8
    }
}';

$condition1 = new Conditions\KeyHasValue('planet', 'earth');
$condition2 = new Conditions\KeyHasValue('planet', 'mars');
$condition3 = new Conditions\KeyHasValue('planet', 'saturn');

$structure = Structure::root(
    Structure::string('planet')
)->addBranch(
    'Branch: earth',
    Structure::object('data', true,
        Structure::integer('humans')
    ),
    $condition1
)->addBranch(
    'Branch: mars',
    Structure::object('data', true,
        Structure::integer('robots')
    ),
    $condition2
)->addBranch(
    'Branch: saturn',
    Structure::empty(),
    $condition3
);
```

- If the value is "earth", the structure is expecting the object `data` and an integer within it called `humans`.
- If the value is "mars", the structure is expecting the object `data` and an integer within it called `robots`.
- If the value is "saturn", there is no additional structure.

## Arrays

To verify each element within an array, you must use branches for each expected element. For example, the following will succeed for string-based elements but fail for anything else.

```php
$json = '{
    "test": ["first", "second", 3]
}';

...

Structure::array('test')->addBranch(
    'Element: string',
    Structure::empty(),
    Conditions\KeyIsType(JsonUtils::STRING)
)
```

The branch uses the `empty()` structure as there is no nested structure. The `KeyIsType()` condition will check if the element's value itself is a string.

### Conditional array elements

In order to add conditions that will not prevent branching and will report errors, you may add conditions to the branch structure (even when using `empty()`).

For example, the following branch will succeed and then check each string element to ensure the value is either "first" or "third", which means the value of "second" will produce an error. The integer value of "3" will already produce an error due to not matching any branch.

```php
$json = '{
    "test": ["first", "second", 3]
}';

...

Structure::array('test')->addBranch(
    'Element: string',
    Structure::empty()->addCondition(new Conditions\HasValue('first', 'third')),
    Conditions\KeyIsType(JsonUtils::STRING)
)
```

## Reports

A report is returned when comparing JSON input to the structure. This report will contain various errors of differing severities.

The report is structured to match the JSON structure and will contain the JSON at the same depth as the report.

For example, given the following structure:

```php
$json = '{
    "test": {
        "hello": "5"
    }
}';

$structure = Structure::root(
    Structure::object('test', true,
        Structure::number('hello')
    )
);

$report = $structure->compare(JsonUtils::deserialize($json));
```

- The root report's `$report->getKey()` will actually be "ROOT", matching the structure of `Structure::root()`. Its `$report->getJson()` will match the initial JSON. It will contain no errors, but will have children.
- The report's first child via `$report->getChildren()` will be for the `test` object structure. It will also contain no errors. `$report->getKey()` will return "test" while `$report->getJson()` will return `{"hello": "5"}`.
- Then that child's child will have a `$report->getKey()` value of "hello". Since it is not an object, `$report->getJson()` will also return `{"hello": "5"}`. This time, however, the report will contain a fatal-level error stating that `hello` is a string and not an integer or double.

These nested reports can be used to generate a tree of errors to show to the user.

### Report methods

| Method | Description |
| - | - |
| `$report->getKey()` | Returns the key of the structure that matches the depth of this report. |
| `$report->getJson()` | Returns the JSON that matches the depth of this report. |
| `$report->getChildReports()` | Returns all child reports of this report. |
| `$report->hasInfo()` | Returns whether or not the report has info-level notices. |
| `$report->hasAnyInfo()` | Returns whether or not the report or its children has info-level notices. |
| `$report->hasWarnings()` | Returns whether or not the report has warning-level errors. |
| `$report->hasAnyWarnings()` | Returns whether or not the report or its children has warning-level errors. |
| `$report->isFatal()` | Returns whether or not the report has fatal-level errors. |
| `$report->hasAnyFatals()` | Returns whether or not the report or its children has fatal-level errors. |
| `$report->getInfo()` | Returns info-level notices. |
| `$report->getWarnings()` | Returns warning-level errors. |
| `$report->getFatals()` | Returns fatal-level errors. |