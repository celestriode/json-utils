<?php namespace Celestriode\JsonUtils;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

class JsonUtils
{
    private const NOT_FOUND = 'Could not find expected key "%s"';

    /**
     * Lints and parses the raw JSON string using Seld\JsonLint.
     * 
     * If the result is valid but null, will return an empty object instead.
     *
     * @param string $raw The JSON string to parse.
     * @return \stdClass
     */
    public static function deserialize(string $raw): \stdClass
    {
        $parser = new JsonParser();

        // Can return NULL if the raw string itself is "null". Rather than doing that, we're just going to store an empty object.
        // Throws ParsingException if it fails to parse. That exception's message will include a formatted error message.

        return strlen($raw) > 0 ? $parser->parse($raw, JsonParser::DETECT_KEY_CONFLICTS) ?? new \stdClass : new \stdClass;
    }

    /**
     * Serialize the object into a JSON string.
     * 
     * TODO: actually have serializing functions. Don't need them right now but might in the future.
     *
     * @param \stdClass $raw The object to turn into a JSON string.
     * @return string
     */
    public static function serialize(\stdClass $raw): string
    {
        return json_encode($raw);
    }

    /**
     * Returns whether or not the key exists. Optionally tests the value of the key
     * against the given predicates.
     * 
     * Will throw errors instead of returning false if $throw is true.
     *
     * @param string $key The key to locate.
     * @param \stdClass $object The object to locate the key within.
     * @param boolean $throw Whether or not to throw exceptions upon failure.
     * @param Predicate\Predicate ...$predicates The predicates to test the value against.
     * @return boolean
     */
    public static function hasKey(string $key, \stdClass $object, bool $throw = false, Predicate\Predicate ...$predicates): bool
    {
        // Check if the key even exists.

        if (!isset($object->{$key})) {

            // Throw if it's supposed to.

            if ($throw) {

                throw new Exception\NotFound(sprintf(self::NOT_FOUND, $key));
            }

            // Otherwise return false.

            return false;
        }

        // Cycle through each predicate and perform the test.

        $value = $object->{$key};

        for ($i = 0, $j = count($predicates); $i < $j; $i++) {

            $predicate = $predicates[$i];

            // Perform the test.

            if (!$predicate->test($value)) {

                // Throw if it's supposed to.

                if ($throw) {

                    throw new Exception\PredicateFailed($predicate->getError());
                }

                // Otherwise return false.

                return false;
            }
        }

        // No issues, return true.

        return true;
    }

    /**
     * Gets a string out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getString(string $key, \stdClass $object): string
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'string') extends Predicate\TypePredicate {
            public function test($value): bool {
                return is_string($value);
            }
        });

        return $object->{$key};
    }

    /**
     * Gets an integer out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getInteger(string $key, \stdClass $object): int
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'integer') extends Predicate\TypePredicate {
            public function test($value): bool {
                return is_int($value);
            }
        });

        // Return result.

        return $object->{$key};
    }

    /**
     * Gets a double out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getDouble(string $key, \stdClass $object): float
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'double') extends Predicate\TypePredicate {
            public function test($value): bool {
                return is_double($value);
            }
        });

        // Return result.

        return $object->{$key};
    }

    /**
     * Gets an object out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getObject(string $key, \stdClass $object): \stdClass
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'object') extends Predicate\TypePredicate {
            public function test($value): bool {
                return $value instanceof \stdClass;
            }
        });

        // Return result.

        return $object->{$key};
    }

    /**
     * Gets an array out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getArray(string $key, \stdClass $object): array
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'array') extends Predicate\TypePredicate {
            public function test($value): bool {
                return is_array($value);
            }
        });

        // Return result.

        return $object->{$key};
    }

    /**
     * Gets a boolean out of the JSON object.
     *
     * @param string $key The key of the value.
     * @param \stdClass $object The JSON object to look within.
     * @return string
     */
    public static function getBoolean(string $key, \stdClass $object): bool
    {
        self::hasKey($key, $object, true, new class($key, gettype($object->{$key} ?? null), 'boolean') extends Predicate\TypePredicate {
            public function test($value): bool {
                return is_bool($value);
            }
        });

        // Return result.

        return $object->{$key};
    }

    /**
     * Returns only the keys within the object.
     *
     * @param \stdClass $object The object within which to get keys from.
     * @return array
     */
    public static function getKeys(\stdClass $object): array
    {
        return array_keys((array)$object);
    }

    /**
     * Returns a list of keys that are not within the provided list of valid keys.
     * Use this for notifying the user of potentially incorrect structure, such as
     * a simple typo. Only checks keys against the root depth of the given object.
     *
     * @param \stdClass $object
     * @param string ...$validKeys
     * @return array
     */
    public static function getInvalidKeys(\stdClass $object, string ...$validKeys): array
    {
        return array_diff(self::getKeys($object), $validKeys);
    }

    /**
     * Performs the specified function for each object that exists within the
     * object. If it encounters anything that isn't an object, it will be
     * ignored.
     *
     * @param \stdClass $object The object to find other objects within.
     * @param \closure $func The function to run against each nested object.
     * @return void
     */
    public static function perObject(\stdClass $object, \closure $func): void
    {
        $keys = self::getKeys($object);

        for ($i = 0, $j = count($keys); $i < $j; $i++) {

            try {

                // Attempt to get the nested object.

                $obj = self::getObject($keys[$i], $object);
            } catch (Exception\JsonException $e) {

                // Skip if it's not an object.

                continue;
            }

            // Perform the function.

            $func($keys[$i], $obj);
        }
    }
}