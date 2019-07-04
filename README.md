# celestriode/json-utils

![Packagist Version](https://img.shields.io/packagist/v/celestriode/json-utils.svg?style=for-the-badge)![GitHub](https://img.shields.io/github/license/celestriode/json-utils.svg?style=for-the-badge)

Provides a handful of utilities for simple interfacing with a JSON-derived object.

1. [General utilities](#general-utilities)
    1. [Deserializing](#deserializing)
    1. [The `Json` object](#json)
    1. [`Json` datatypes](#json-datatypes)
    1. [The `JsonCollection` object](#json-collection)
    1. [Normalizing](#normalizing)
1. [Predicates](#predicates)
    1. [Packaged predicates](#packaged-predicates)
1. [Structural validation](#structure)
    1. [Defining a structure](#structure-defining)
    1. [Reports](#reports)
    1. [The `Report` object](#report)
    1. [Statistical reports](#statistics)
    1. [Audits](#audits)
    1. [Adding an audit to a structure](#adding-audits)

# <a name="general-utilities">General utilities</a>

Many utilities in this library require the use of a `Celestriode\JsonUtils\Json` object, which can be created manually or obtained by deserializing a JSON-formatted string.

## <a name="deserializing">Deserializing</a>

If you do not already have a [Json](#json) object, start by deserializing a JSON-formatted string.

```php
use Celestriode\JsonUtils\JsonUtils;

$string = '{"key": "value"}';

$json = JsonUtils::deserialize($string);
```

This uses the [seldaek/jsonlint](https://github.com/Seldaek/jsonlint) library for linting and will not catch errors thrown by it; that's up to you to handle.

## <a name="json">The `Json` object</a>

After deserializing, the `Celestriode\JsonUtils\Json` object is returned. This has a number of interfacing methods to more easily access data of the JSON field.

| Method | Description |
| - | - |
| `$json->getParent(): ?Json` | If this field is a child of an object or array, its parent is returned. |
| `$json->getKey(): ?string` | If this field has a key, it will be returned. There are cases where no key exists, such as in elements or at the root. |
| `$json->getValue()` | Returns the raw value of the field. For example, if the field is a string, it will return a string. If the field is an object, it will return `\stdClass`. If the field is an array, it will return `array`. |
| `$json->getType(): ?int` | Returns the datatype of the field. By default, this value will be determined automatically when using `setValue()`, whether manually calling that method or when providing a value to the constructor.<br><br>See [`Json` datatypes](#json-datatypes) for a list of constants that match the returned value. |
| `$json->isType(int $type): bool` | Returns whether or not the field matches the specified type.<br><br>See [`Json` datatypes](#json-datatypes) for a list of constants that match the input value. |
| `$json->checkJson(IPredicate $predicate, \closure $func = null): int` | Takes in a `Celestriode\JsonUtils\IPredicate` object and tests it against the field. If it passes, it will run `$func` if provided, which will be supplied with the `Json` object.<br><br>Returns the number of times it succeeds. By default this will be at most 1, but the [`JsonCollection` class](#json-collection) can return a higher value. |
| `$json->toJsonString(): string` | Turns the field into a JSON-based string, complete with key. |
| `$json->toString(): string` | Turns just the value into a JSON-based string. |

### Objects

The following are methods specific to JSON objects. If `!$json->isType(Json::OBJECT)`, almost all of these methods will throw a `Celestriode\JsonUtils\Exception\WrongType` exception. The one outlier is `hasField()`, which will simply return false instead of throwing.

| Method | Description |
| - | - |
| `$json->getKeys(): array` | Returns an array of strings, being the keys of the direct children within this object. |
| `$json->getInvalidKeys(string ...$validKeys): array` | Takes in a list of valid keys and returns a list of keys that were not in that list. |
| `$json->hasField(string $key, int $type = Json::ANY): bool` | Takes in the key of a potential child and returns whether or not the object contains that child. Also takes in an optional datatype to check if the child is of that datatype. |
| `$json->getField(string $key, int $expectedType = Json::ANY): Json` | Returns a child with the specified key and optionally has the specified datatype. Throws `Celestriode\JsonUtils\Exception\NotFound` if the child wasn't found, or `Celestriode\JsonUtils\Exception\WrongType` if the child was of the wrong datatype. |
| `$json->getFields(int $type = Json::ANY): JsonCollection` | Returns all children within the object, or all children of just the specified datatype, as a [`Celestriode\JsonUtils\JsonCollection` object](#json-collection). |
| `$json->getBoolean(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is a boolean, or optionally null. |
| `$json->getInteger(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is an integer, or optionally null. |
| `$json->getDouble(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is a double, or optionally null. |
| `$json->getNumber(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is an integer or double, or optionally null. |
| `$json->getString(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is a string, or optionally null. |
| `$json->getScalar(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is a boolean, integer, double, or string, or optionally null. |
| `$json->getObject(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is an object, or optionally null. |
| `$json->getArray(string $key, bool $nullable = false): Json` | Returns a child with the specified key that is an array, or optionally null. |

### Arrays

The following are methods specific to JSON arrays. If `!$json->isType(Json::ARRAY)`, all of these methods will throw a `Celestriode\JsonUtils\Exception\WrongType` exception.

| Method | Description |
| - | - |
| `$json->getElement(int $index): Json` | Returns the element at the specified index within the array. Throws `Celestriode\JsonUtils\Exception\NotFound` if the element did not exist. |
| `$json->getElements(int $type = Json::ANY): JsonCollection` | Returns all elements in the array, or all elements of just the specified datatype, as a [`Celestriode\JsonUtils\JsonCollection` object](#json-collection). |

### Setters

If creating a `Json` object manually, or you would like to manipulate the returned object, there are also a number of setters.

| Method | Description |
| - | - |
| `$json->setParent(Json $parent = null): void` | Sets the parent of the field. |
| `$json->setKey(string $key = null): void` | Sets (or removes) the key of the field. |
| `$json->setValue($value = null): void` | Sets (or removes) the value of the field. By default this will also run `setType()` based on the datatype of the value. |
| `$json->setType(int $type): void` | Sets the datatype of the field.<br><br>See [`Json` datatypes](#json-datatypes) for a list of constants that match the input value. |

## <a name="json-datatypes">`Json` datatypes</a>

The following is a list of constants provided via the `Celestriode\JsonUtils\Json` class to correspond to each datatype that can be present in JSON.

```php
use Celestriode\JsonUtils\Json;

$null = Json::NULL; // NULL fields
$bool = Json::BOOLEAN; // Boolean fields
$int = Json::INTEGER; // Integer fields
$double = Json::DOUBLE; // Double fields
$string = Json::STRING; // String fields
$object = Json::OBJECT; // Object fields
$array = Json::ARRAY

$any = Json::ANY; // Any of the above
$number = Json::NUMBER; // Matches Json::INTEGER and Json::DOUBLE
$scalar = Json::SCALAR; // Matches Json::BOOL, Json::NUMBER, and Json::STRING
```

These would primarily be used when checking the datatype of a `Json` object.

```php
use Celestriode\JsonUtils\Json;

$json = new Json('key', 'value that will be parsed as a string');

var_dump($json->isType(Json::STRING)); // true
var_dump($json->isType(Json::OBJECT)); // false
var_dump($json->isType(Json::NUMBER)); // false
var_dump($json->isType(Json::SCALAR)); // true
var_dump($json->isType(Json::ANY)); // true
var_dump($json->isType(Json::BOOLEAN | Json::STRING)); // true
```

## <a name="json">The `JsonCollection` object</a>

The `Celestriode\JsonUtils\JsonCollection` object, which extends `Celestriode\JsonUtils\Json`, is primarily used as a result of `$json->getFields()` and `$json->getElements()`. It holds a list of `Celestriode\JsonUtils\Json` objects.

```php
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\JsonCollection;

$jsonCollection = $json->getElements(); // Returns all children of a JSON array as a JsonCollection

$collection = $jsonCollection->getCollection(); // Returns the list of Json classes in the collection.
$count = $jsonCollection->count(); // Returns the number of Json classes in the collection.
```

It will also override some methods from `Json`:

| Method | Description |
| - | - |
| `$jsonCollection->setValue($value = null): void` | Can only set the value if it wasn't already set. |
| `$jsonCollection->setType(int $type): void` | Can only set the type if it wasn't already set. |
| `$json->checkJson(IPredicate $predicate, \closure $func = null): int` | Runs the test against all objects in the collection, and runs `$func` with each one that succeeds. The return value will be the number of tests that passed, rather than just 0 or 1. |


## <a name="normalizing">Normalizing</a>

These methods transform an integer into a datatype string, and a datatype string into an integer. The integer values correspond to the constants provided by the [`Json` datatypes](#json-datatypes), while the string values correspond to PHP's `gettype()` return values.

```php
use Celestriode\JsonUtils\JsonUtils;

$string = JsonUtils::normalizeTypeInteger(Json::ARRAY); // result: "array"
$integer = JsonUtils::normalizeTypeString('array'); // result: 16 AKA Json::ARRAY
```

# <a name="Predicates">Predicates</a>

Various features, such as `$json->checkJson()`, make use of predicates to perform a test. All predicates must implement `Celestriode\JsonUtils\IPredicate`, which comes with two required methods: a test to check the [`Json` object](#json), and a method to return a [`Report` object](#report) with a preset report (where applicable).

```php
use Celestriode\JsonUtils\IPredicate;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Report;

class JsonIsArray implements IPredicate
{
    /**
     * Will only return true if the JSON is an array.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        return $json->isType(Json::ARRAY);
    }

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report
    {
        return Report::warning('Predicate failed because it was not an array');
    }
}

$json = new Json('key', []);
$json->checkJson(new JsonIsArray()); // Result: true
```

A standard predicate `Celestriode\JsonUtils\Predicates\Predicate` is available to avoid having to supply the report every time, since the test is typically more important. Simply extend that predicate and add the test.

```php
use Celestriode\JsonUtils\Predicates\Predicate;
use Celestriode\JsonUtils\Json;

class ATestCase extends Predicate
{
    public function test(Json $json): bool
    {
        return $json->hasField('key');
    }
}
```

## <a name="packaged-predicates">Packaged predicates</a>

There are also a number of predicates that come with the library available to use. These are all in the `Celestriode\JsonUtils\Predicates` namespace.

| Predicate | Description |
| - | - |
| `AlwaysTrue` | Only ever returns true. Useful for testing. |
| `AlwaysFalse` | Only ever returns false. Useful for testing. |
| `DataType(int $type)` | Takes in a datatype when creating the predicate. When testing, will return whether the [`Json` object]($json) matches that datatype. |
| `HasValue(...$values)` | Takes in a list of valid values that the [`Json` object is allowed to have](#json). When testing, will return whether or not the object's value matches one in the list. If the `Json` object is not scalar, it will also return false. |
| `SiblingHasValue(string $sibling, ...$values)` | Takes in the key of a sibling and a list of valid values. When testing, if there is no parent, returns false. Otherwise, it runs `HasValue()`'s test on the sibling. |

For example, the following runs a test against a `Json` object to ensure that it, as a scalar value, matches any of the listed values.

```php
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Predicates\HasValue;

$json = new Json('key', 'value');
$json->checkJson(new HasValue('test', 'another', 'value', 'blah')); // Result: true
$json->checkJson(new HasValue('test', 'hello', 'goodbye')); // Result: false
```

## <a name="">Singleton predicates</a>

Anything extending `Celestriode\JsonUtils\Predicates\Predicate` (such as all of the [packaged predicates](#packaged-predicates)) or making use of the `Celestriode\JsonUtils\TMultiSingleton` trait will have access to the `instance()` static method. This will create a singleton of the predicate to reduce memory load from having unnecessary duplicate objects in memory.

This should not be used if the predicate needs to store volatile data to, for example, use in the `test()` or `getReport()` methods. In those cases, always create a new class. A general rule of thumb is if you're using `__construct()` in a custom predicate, you should not be using `instance()`.

In the case of packaged predicates, `AlwaysTrue` and `AlwaysFalse` should **always** use `instance()`. The other predicates should not.

```php
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Predicates\AlwaysTrue;

$json = new Json('key', 'value');
$json->checkJson(AlwaysTrue::instance()); // Do this
$json->checkJson(new AlwaysTrue()); // DO NOT do this
```

# <a name="structure">Structural validation</a>

You can compare a [`Json` object](#json) to an expected structure to generate [reports](#reports) on the structure itself. This is done using the `Celestriode\JsonUtils\Structure` class, which comes with many helper methods to simplify structure definitions.

## <a name="structure-defining">Defining a structure</a>

The following are a list of methods available to aid in building a structure. They define the expected key names and datatypes within a JSON structure.

### Primitive values

These methods primarily define primitive values as seen in JSON.

| Method | Description |
| - | - |
| `Structure::root(int $type = Json::OBJECT, Structure ...$children): Structure` | The beginning of a structure. `$children` are added as either child fields or elements depending on if `$type` is an object or array. |
| `Structure::boolean(string $key = null, bool $required = true): Structure` | Expects a boolean value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::integer(string $key = null, bool $required = true): Structure` | Expects an integer value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::double(string $key = null, bool $required = true): Structure` | Expects a double value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::number(string $key = null, bool $required = true): Structure` | Expects an integer or double value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::string(string $key = null, bool $required = true): Structure` | Expects a string value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::scalar(string $key = null, bool $required = true): Structure` | Expects a boolean, integer, double, or string value with optional `$key`. Can be omitted if `$required` is false. |
| `Structure::null(string $key = null, bool $required = true): Structure` | Expects a null value with optional `$key`. Can be omitted if `$required` is false. |

For example, the following specifies a structure that has a root object. Within it is an optional string field with the key "hello", and a required numeric field with the key "goodbye".

```php
use Celestriode\JsonUtils\Structure;

$structure = Structure::root(Json::OBJECT,
    Structure::string('hello', false),
    Structure::number('goodbye)
);

/*
MATCHES:

{
    "hello": "test",
    "goodbye": 4
}

{
    "goodbye": 1
}
*/

/*
DOES NOT MATCH:

{
    "hello": "test",
    "goodbye": "string"
}

{
    "hello": 9,
    "goodbye": 5
}

{
    "hello": "9"
}
*/
```

### Object values

The `Structure::object(string $key = null, bool $required = true, Structure ...$children): Structure` method defines an object with optional `$key`, `$required` flag, and `$children` of the object to continue comparison with fields nested inside.

```php
Structure::root(Json::OBJECT,
    Structure::object('content', true,
        Structure::integer('id'),
        Structure::string('name')
    )
);

/*
MATCHES:

{
    "content": {
        "id": 4,
        "name": "test"
    }
}
*/
```

### Array values

The `Structure::array(string $key = null, bool $required = true, Structure ...$elements): Structure` method defines an array with optional `$key`, `$required` flag, and a variety of valid `$elements` within the array. Its direct children essentially define the datatypes of the elements, and are therefore keyless. You can define multiple child elements to accept a variety of different datatypes within the array.

```php
Structure::root(Json::OBJECT,
    Structure::array('stuff', true,
        Structure::string(),
        Structure::boolean(),
        Structure::object(null, true,
            Structure::integer('id'),
            Structure::string('name')
        )
    )
);

/*
MATCHES:

{
    "stuff": [
        "test",
        true,
        {
            "id": 4,
            "name": "blah"
        }
    ]
}
*/
```

### Mixed values

The `Structure::mixed(string $key = null, int $type = Json::ANY, bool $required = true, Structure ...$children): Structure` method allows you to specify any datatype combo you want. An optional `$key`, `$required` flag, and `$children` can be supplied. The children will be used as either child fields if the datatype is an object, or as child elements if the datatype is an array.

For example, the following specifies a mixed structure within an array that can be a string, boolean, or an object with the specified children.

```php
Structure::root(Json::OBJECT,
    Structure::array('stuff', true,
        Structure::mixed(null, Json::STRING | Json::BOOLEAN | Json::OBJECT, true,
            Structure::integer('id'),
            Structure::string('name')
        )
    )
);

/*
MATCHES:

{
    "stuff": [
        "test",
        true,
        {
            "id": 4,
            "name": "blah"
        }
    ]
}
*/
```

### Placeholder values

The `Structure::placeholder(int $type, Structure ...$children): Structure` method defines a value where the key can be anything, as long as it matches the expected `$type`. Any fields that do not match the type can still be validated by other defined structures at the same depth. `$children` are added as either child fields or elements depending on if `$type` is an object or array.

```php
Structure::root(Json::OBJECT,
    Structure::integer('id'),
    Structure::placeholder(Json::STRING)
);

/*
MATCHES:

{
    "id": 5,
    "any": "key",
    "with": "a",
    "string": "as",
    "the": "value",
    "don't": "dead",
    "open": "inside"
}
*/
```

### Branching values

The `Structure::branch(string $label, IPredicate $predicate, Structure ...$branches): Structure` method defines a structure that will introduce new `$structures` to the tree provided that the `$predicate` succeeds. The `$label` is simply a friendly name to give the branch itself, which can be used in [reports](#reports).

```php
$structure = Structure::root(Json::OBJECT,
    Structure::string('id'),

    // Branched if the value of "id" is "first"

    Structure::branch('Branch A', new SiblingHasValue('id', 'first'),
        Structure::integer('test')
    ),

    // Branched if the value of "id" is "second"

    Structure::branch('Branch B', new SiblingHasValue('id', 'second'),
        Structure::boolean('correct')
    )
);

/*
MATCHES:

{
    "id": "first",
    "test": 4
}

{
    "id": "second",
    "correct": true
}
/*/
```

### Ascending values

The `Structure::ascend(UuidInterface $ancestor, string $key = null, bool $required = true): Structure` method makes use of an ancestor's structure to validate the JSON at this depth. This can be used for recursive structures. The ancestor to locate is defined using `Ramsey\Uuid\UuidInterface`, and the ancestor itself must have the specified UUID by using the `setUuid()` method on the ancestral structure.

If no valid ancestor was found, `Celestriode\JsonUtils\Exception\BadStructure` is thrown.

```php
use Celstriode\JsonUtils\Structure;
use Ramsey\Uuid\Uuid;

$uuid = Uuid::fromString('1533b1f4-e150-4d04-a770-b128b0eadadf');

$structure = Structure::root(Json::OBJECT,
    Structure::string('name'),
    Structure::array('children', false,
        Structure::ascend($uuid)
    )
)->setUuid($uuid);

/*
MATCHES:

{
    "name": "grandparent",
    "children": [
        {
            "name": "aunt/uncle"
        },
        {
            "name": "parent",
            "children": [
                {
                    "name": "me"
                }
            ]
        }
    ]
}
/*/
```

## <a name="reports">Reports</a>

Once you have defined a structure, you can run the `compare(Json $json, Reports $reports = null): Reports` method. This will take in the deserialized JSON, and can optionally take in a prebuilt `Celestriode\JsonUtils\Structure\Reports` object. It will populate the reports and return the same object. If no reports are provided, it will create a new one.

The tree structure of reports will match the tree structure defined with `Celestriode\JsonUtils\Structure`. Each level of reports will contain the relevant `Celestriode\JsonUtils\Json` object for that depth, along with any reports of various levels.

The individual reports themselves make use of the [`Celestriode\JsonUtils\Structure\Report` class](#report).

```php
use Celestriode\JsonUtils\JsonUtils;
use Celestriode\JsonUtils\Structure;

// Deserialize raw JSON.

$json = JsonUtils::deserialize('{"string": "with this value"}');

// Define the expected structure.

$structure = Structure::root(Json::OBJECT,
    Structure::string('string')
);

// Compare it to receive reports.

$reports = $structure->compare($json);
```

To simply check if the structure was valid, you can run the `hasAnyErrors()` method from the reports. If true, it failed in some manner.

```php
$reports->hasAnyErrors(); // true if mismatched structure
```

### Reports methods

The following are various methods available with reports.

| Method | Description |
| - | - |
| `$report->addReport(Report $report): void` | Adds a [report](#report) to the list of reports. That report will itself define its severity. |
| `$report->getKey(): ?string` | Returns the key of the structure that matches the depth of this report. |
| `$report->getJson(): ?Json` | Returns the JSON that matches the depth of this report. |
| `$report->getChildReports(): array` | Returns all child reports of this report. |
| `$report->hasInfo(): bool` | Returns whether or not the report has info-level notices. |
| `$report->hasAnyInfo(): bool` | Returns whether or not the report or its children has info-level notices. |
| `$report->hasWarnings(): bool` | Returns whether or not the report has warning-level errors. |
| `$report->hasAnyWarnings(): bool` | Returns whether or not the report or its children has warning-level errors. |
| `$report->isFatal(): bool` | Returns whether or not the report has fatal-level errors. |
| `$report->hasAnyFatals(): bool` | Returns whether or not the report or its children has fatal-level errors. |
| `$report->hasMessages(): bool` | Returns whether or not the report has any level of errors. |
| `$report->hasAnyMessages(): bool` | Returns whether or not the report or its children has any level of errors. |
| `$report->hasErrors(): bool` | Returns whether or not the report has warning-level or fatal-level errors. |
| `$report->hasAnyErrors(): bool` | Returns whether or not the report or its children has warning-level or fatal-level errors. |
| `$report->getInfo()` | Returns info-level notices. |
| `$report->getWarnings()` | Returns warning-level errors. |
| `$report->getFatals()` | Returns fatal-level errors. |
| `$report->getMessages()` | Returns errors of any level. |
| `$report->getErrors()` | Returns warning-level and fatal-level errors. |
| `$report->getAllInfo()` | Returns info-level notices, including from its children. |
| `$report->getAllWarnings()` | Returns warning-level errors, including from its children. |
| `$report->getAllFatals()` | Returns fatal-level errors, including from its children. |
| `$report->getAllMessages()` | Returns errors of any level, including from its children. |
| `$report->getAllErrors()` | Returns warning-level and fatal-level errors, including from its children. |

## <a name="report">The `Report` object</a>

Creating a report can be done with 3 primary helper methods, which define the severity of the report:

```php
use Celestriode\JsonUtils\Structure\Report;

$info = Report::info('this is the info message');
$warning = Report::warning('this is the warning message');
$fatal = Report::fatal('this is the fatal message');
```

The format makes use of PHP's `sprintf()` function, allowing you to more easily fill in data. The standard `Report` class comes with two methods to sanitize input, since reports are primarily going to be displayed to the user. These methods are `Report::key(string ...$keys): string` and `Report::value(...$values): string`. These methods should especially be used on JSON data that you plan to display, as `htmlentities()` is used to prevent embedding HTML.

```php
use Celestriode\JsonUtils\Structure\Report;

$warning = Report::warning('Warning with keys %s and value %s', Report::key('key1', 'key2'), Report::value('value1'));

// Result:
// Warning with keys "key1", "key2" and value &lt;code&gt;value1&lt;/code&gt;
```

## <a name="statistics">Statistical reports</a>

Alongside the primary `Reports` class is the `Celestriode\JsonUtils\Structure\StatisticalReports` class. This class will automatically populate `Celestriode\JsonUtils\Structure\Statistics` using the incoming JSON structure. If you would prefer to use this, you can pass it into the `compare()` method when validating a structure.

```php
use Celestriode\JsonUtils\JsonUtils;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\StatisticalReports;

// Deserialize raw JSON.

$json = JsonUtils::deserialize('{"string": "with this value"}');

// Define the expected structure.

$structure = Structure::root(Json::OBJECT,
    Structure::string('string')
);

// Compare it to receive statistical reports.

$statisticalReports = $structure->compare($json, new StatisticalReports());
```

Use the `getStatistics(): Statistics` method to get the relevant `Statistics` object. With that object, use the method `getStatistics(): array`, which will return an array structure with generated statistics of the JSON data.

```php
$statistics = $statisticalReports->getStatistics();

print_r($statistics->getStatistics());
```

## <a name="audits">Audits</a>

When validating a JSON structure, sometimes it's necessary to get more specific than simply checking if fields exist. Audits allow you to get much more personal with the incoming structure. In particular, audits should be used to add reports, which differs from predicates which should not directly add reports.

An audit is defined by the `Celestriode\JsonUtils\IAudit` interface, where the `audit()` method takes in the structure, Json, and reports all at the current depth of the JSON tree. However, like predicates, there is a standard audit available at `Celestriode\JsonUtils\Structure\Audits\Audit`. It also makes use of the `Celestriode\JsonUtils\TMultiSingleton` trait, giving you access to `instance()` when necessary.

You can simple extend that audit class to gain access to that feature while specifying the `audit()` method yourself. For example, the following adds an error if the value of the audit wasn't the correct, hard-coded value.

```php
use Celestriode\JsonUtils\Structure\Audits\Audit;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Reports;

class HasValue extends Audit
{
    /**
     * Adds a warning if the value of the Json structure wasn't correct.
     *
     * @param Structure $structure The structure at the current depth.
     * @param Json $json The Json at the current depth.
     * @param Reports $reports Reports at the current depth.
     * @return void
     */
    public function audit(Structure $structure, Json $json, Reports $reports): void
    {
        if (!$json->getValue() === 'expected value') {

            $reports->addReport(Report::warning(
                'The value of %s should have been "expected value", was %s',
                Report::key($json->getKey()),
                Report::value($json->toString())
            ));
        }
    }
}
```

### Packaged audits

There are also a number of audits that come with the library available to use. These are all in the `Celestriode\JsonUtils\Structure\Audits` namespace.

| Predicate | Description |
| - | - |
| `HasValue(...$values)` | Takes in a list of valid values that the [`Json` object is allowed to have](#json). Adds the report returned from the `HasValue()` predicate should the audit fail. |
| `SiblingHasValue(string $sibling, ...$values)` | Takes in the key of a sibling and a list of valid values and ensures the sibling has those values. Adds the report returned from the `SiblingHasValue()` predicate should the audit fail. |

## <a name="adding-audits">Adding an audit to a structure</a>

Simply use the `addAudit(IAudit $audit, IPredicate ...$predicates): Structure` method on a structure to set an audit. You can optionally specify `$predicates`, which must all pass **before** running the audit. This can be useful to prevent adding reports unless a certain criteria is met beforehand.

```php
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Audits\HasValue;
use Celestriode\JsonUtils\Predicates\AlwaysFalse;

$structure = Structure::root(Json::OBJECT,
    Structure::string('test')->addAudit(new HasValue('first', 'second', 'third')),
    Structure::string('blah', false)->addAudit(new HasValue('a', 'b'), AlwaysFalse::instance())
);

/*
MATCHES:

{
    "test": "second",
    "blah": "c" // This matches because the audit was never run due to the AlwaysFalse predicate
}
*/
```

# <a name="example">Final example</a>

The following complex structure can successfully match against Minecraft's text component, though some validation (such as NBT parsing) is missing.

```php
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Predicates;
use Celestriode\JsonUtils\Structure\Audits;
use Ramsey\Uuid\Uuid;

$uuid = Uuid::fromString('742a8779-2c69-4d5f-8731-19f0d4f40ff7');

$structure = Structure::root(Json::ARRAY | Json::SCALAR | Json::OBJECT)
->setUuid($uuid)
->addElements(

    // If the structure's root is an array, then its elements can be the same as the root.

    Structure::ascend($uuid)
)
->addChildren(

    // Textual

    Structure::string('text', false),
    Structure::string('selector', false),
    Structure::string('keybind', false),
    Structure::string('translate', false),
    Structure::array('with', false)->addElements(
        Structure::ascend($uuid)
    ),
    Structure::object('score', false,
        Structure::string('name'),
        Structure::string('objective'),
        Structure::string('value', false)
    ),
    Structure::string('nbt', false),
    Structure::string('block', false),
    Structure::string('entity', false),
    Structure::boolean('interpret', false),

    // Formatting

    Structure::string('color', false),
    Structure::boolean('bold', false),
    Structure::boolean('italic', false),
    Structure::boolean('underlined', false),
    Structure::boolean('obfuscated', false),
    Structure::boolean('strikethrough', false),

    // Events: click
    
    Structure::object('clickEvent', false,
        Structure::string('action')->addAudit(new Audits\HasValue('open_url', 'open_file', 'run_command', 'suggest_command', 'change_page')),

        // Branch: open_url

        Structure::branch('click: open URL', new Predicates\SiblingHasValue('action', 'open_url'), Structure::string('value')),

        // Branch: open_file

        Structure::branch('click: open file', new Predicates\SiblingHasValue('action', 'open_file'),
            Structure::string('value')
        ),

        // Branch: run_command

        Structure::branch('click: run command', new Predicates\SiblingHasValue('action', 'run_command'),
            Structure::string('value')
        ),

        // Branch: suggest_command

        Structure::branch('click: suggest command', new Predicates\SiblingHasValue('action', 'suggest_command'),
            Structure::string('value')
        ),

        // Branch: change_page

        Structure::branch('click: change page', new Predicates\SiblingHasValue('action', 'change_page'),
            Structure::string('value')
        )
    ),

    // Events: hover

    Structure::object('hoverEvent', false,
        Structure::string('action')->addAudit(new Audits\HasValue('show_text', 'show_item', 'show_entity')),

        // Branch: show_text

        Structure::branch('hover: show text', new Predicates\SiblingHasValue('action', 'show_text'),
            Structure::ascend($uuid, 'value')
        ),

        // Branch: show_item

        Structure::branch('hover: show item', new Predicates\SiblingHasValue('action', 'show_item'),
            Structure::string('value')
        ),

        // Branch: show_entity

        Structure::branch('hover: show entity', new Predicates\SiblingHasValue('action', 'show_entity'),
            Structure::string('value')
        )
    ),

    // Events: insertion

    Structure::string('insertion', false),

    // Recursive via "extra" array.

    Structure::array('extra', false,
        Structure::ascend($uuid)
    )
);
```