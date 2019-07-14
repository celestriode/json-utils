<?php namespace Celestriode\JsonUtils;

use Celestriode\JsonUtils\Exception\WrongType;
use Celestriode\JsonUtils\Exception\NotFound;
use Celestriode\JsonUtils\Structure\IStatisticalReportContext;
use Celestriode\JsonUtils\Structure\Statistics;

class Json implements IStatisticalReportContext
{
    // TODO: more standard errors.
    private const WRONG_FIELD_TYPE = 'Cannot get field "%s" because it was of type "%s" instead of the expected type "%s"';

    const ANY = -1;
    const INTEGER = 1;
    const DOUBLE = 2;
    const BOOLEAN = 4;
    const STRING = 8;
    const ARRAY = 16;
    const OBJECT = 32;
    const NULL = 64;
    const NUMBER = self::INTEGER | self::DOUBLE;
    const SCALAR = self::NUMBER | self::BOOLEAN | self::STRING;

    protected $key;
    protected $value;
    protected $type;

    protected $parent;

    /**
     * This is the housing unit for data retrieved from a JSON input. This data
     * is used when comparing to structures.
     *
     * @param string $raw The raw string before being decoded.
     * @param string $key The key of the value, if applicable. Not applicable for root structures or array elements.
     * @param mixed $value The decoded JSON value itself.
     */
    public function __construct(string $key = null, $value = null, self $parent = null)
    {
        $this->setKey($key);
        $this->setValue($value);
        $this->setParent($parent);
    }

    /**
     * Sets the parent Json of this Json, or removes it entirely.
     *
     * @param self $parent The parent to set.
     * @return void
     */
    public function setParent(self $parent = null): void
    {
        $this->parent = $parent;
    }

    /**
     * Returns the parent Json of this Json, if applicable.
     *
     * @return self|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Returns the key associated with the JSON data, if applicable.
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Returns the JSON value after the raw string was decoded.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns the datatype of the JSON input.
     *
     * @return integer|null
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * Sets the key associated with the JSON data, if applicable.
     *
     * @param string $key
     * @return void
     */
    public function setKey(string $key = null): void
    {
        $this->key = $key;
    }

    /**
     * Sets the decoded JSON, whatever datatype it may be.
     *
     * @param mixed $value The decoded JSON value.
     * @return void
     */
    public function setValue($value = null): void
    {
        $this->value = $value;
        $this->setType(JsonUtils::normalizeTypeString(gettype($value)));
    }

    /**
     * Sets the datatype of the JSON input.
     *
     * @param integer $type
     * @return void
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * Returns whether or not the datatype of the JSON matches the input.
     *
     * @param integer $type The datatype to verify.
     * @return boolean
     */
    public function isType(int $type): bool
    {
        return ($this->getType() & $type) !== 0;
    }

    /**
     * Returns the keys of the fields within the object.
     *
     * @return array
     */
    public function getKeys(): array
    {
        // Throw if this isn't an object.

        if (!$this->isType(self::OBJECT)) {

            throw new WrongType('Cannot get keys within "' . $this->getKey() . '" because it is of type "' . implode(', ', JsonUtils::normalizeTypeInteger($this->getType())) . '" instead of type "object"');
        }

        // Otherwise return the keys of the object.

        return array_keys((array)$this->getValue());
    }

    /**
     * Returns a list of keys that are not within the provided list of valid keys.
     * Use this for notifying the user of potentially incorrect structure, such as
     * a simple typo. Only checks keys against the root depth of the given object.
     *
     * @param string ...$validKeys
     * @return array
     */
    public function getInvalidKeys(string ...$validKeys): array
    {
        return array_values(array_diff($this->getKeys(), $validKeys));
    }

    /**
     * Returns whether or not the object has the specified field.
     *
     * @param string $key The name of the field to locate.
     * @return boolean
     */
    public function hasField(string $key, int $type = self::ANY): bool
    {
        if ($type === Json::ANY) {

            return $this->isType(self::OBJECT) && property_exists($this->getValue(), $key);
        }

        return $this->isType(self::OBJECT) && property_exists($this->getValue(), $key) && ($type === Json::ANY || ($type !== Json::ANY && $this->getField($key)->isType($type)));
    }

    /**
     * Returns all elements within the array as Json classes.
     *
     * @param integer $type The datatype of elements to get.
     * @return array
     */
    public function getElements(int $type = self::ANY): JsonCollection
    {
        // Throw if this isn't an array.

        if (!$this->isType(self::ARRAY)) {

            throw new WrongType('Cannot get elements because this structure is of type "' . implode(', ', JsonUtils::normalizeTypeInteger($this->getType())) . '" when it must be of type "array"');
        }

        // Cycle through all elements and create a new JSON class from them.

        $raw = [];
        $collection = [];

        for ($i = 0, $j = count($this->getValue()); $i < $j; $i++) {

            $current = $this->getValue()[$i];
            $actualType = JsonUtils::normalizeTypeString(gettype($current));

            // If null and accepting null or matches type, add field.

            if (($actualType & $type) !== 0) {

                $raw[] = $current;
                $collection[] = new self(null, $current, $this);
            }

        }

        // Return the completed collection.

        return new JsonCollection($this->getKey(), $raw, ...$collection);
    }

    /**
     * Returns a single element by index.
     *
     * @param integer $index The index to get the element at.
     * @return self
     */
    public function getElement(int $index): self
    {
        // Throw if this isn't an array.

        if (!$this->isType(self::ARRAY)) {

            throw new WrongType('Cannot get elements because this structure is of type "' . implode(', ', JsonUtils::normalizeTypeInteger($this->getType())) . '" when it must be of type "array"');
        }

        // Throw if the index didn't exist.

        if (!isset($this->getValue()[$index])) {

            throw new NotFound('Could not find index "' . $index . '" within array "' . $this->getKey() . '"');
        }

        // Return the element.

        return new self(null, $this->getValue()[$index], $this);
    }

    /**
     * Returns all fields within the object as Json classes.
     *
     * @param integer $type The datatype of elements to get.
     * @return JsonCollection
     */
    public function getFields(int $type = self::ANY): JsonCollection
    {
        // Throw if this isn't an object.

        if (!$this->isType(self::OBJECT)) {

            throw new WrongType('Cannot get fields because this structure is of type "' . implode(', ', JsonUtils::normalizeTypeInteger($this->getType())) . '" when it must be of type "object"');
        }

        $keys = $this->getKeys();
        $raw = new \stdClass();
        $collection = [];

        // Cycle through each key in this object and get the Json from it.

        for ($i = 0, $j = count($keys); $i < $j; $i++) {

            $current = $this->getField($keys[$i], self::ANY);

            // If the Json is of the correct type and has a key, add it to the collection.

            if ($current->isType($type) && $current->getKey() !== null) {

                $raw->{$current->getKey()} = $current->getValue();
                $collection[] = $current;
            }
        }

        // Return the completed collection.

        return new JsonCollection($this->getKey(), $raw, ...$collection);
    }

    /**
     * Returns a specific field within the object.
     *
     * @param string $key The name of the field.
     * @return self
     */
    public function getField(string $key, int $expectedType = self::ANY): self
    {
        // Throw if this JSON structure isn't an object.

        if (!$this->isType(self::OBJECT)) {

            throw new WrongType('Cannot get field "' . $key . '" because this structure is of type "' . implode(', ', JsonUtils::normalizeTypeInteger($this->getType())) . '" when it must be of type "object"');
        }

        // Throw if this JSON object didn't have the field.

        if (!$this->hasField($key)) {

            throw new NotFound('Cannot find field "' . $key . '"');
        }

        // Throw if the field was not of the expected type.

        if ($expectedType !== self::ANY) {

            $actualType = JsonUtils::normalizeTypeString(gettype($this->getValue()->{$key}));

            if (($actualType & $expectedType) === 0) {
    
                throw new WrongType(sprintf(self::WRONG_FIELD_TYPE, $key, implode(', ', JsonUtils::normalizeTypeInteger($actualType)), implode(', ', JsonUtils::normalizeTypeInteger($expectedType))));
            }
        }

        // Create intermediate JSON class.

        return new self($key, $this->getValue()->{$key}, $this);
    }

    /**
     * Passes the Json object to the predicate to ensure it passes.
     * If so, it runs the optionally-supplied function.
     * 
     * Returns 1 for success, 0 for failure.
     *
     * @param IPredicate $predicate The predicate to test the Json objects against.
     * @param \closure $func The function to run for predicates that succeed.
     * @return boolean
     */
    public function checkJson(IPredicate $predicate, \closure $func = null): int
    {
        // Check the predicate.

        if ($predicate->test($this)) {

            // Run the function if applicable.

            if ($func !== null) {

                $func($this);
            }

            // Return 1.

            return 1;
        }

        // Otherwise it failed, return 0.

        return 0;
    }

    /**
     * Returns a boolean field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getBoolean(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::BOOLEAN | self::NULL : self::BOOLEAN;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns an integer field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getInteger(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::INTEGER | self::NULL : self::INTEGER;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns a double field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getDouble(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::DOUBLE | self::NULL : self::DOUBLE;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns an integer or double field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getNumber(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::NUMBER | self::NULL : self::NUMBER;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns a string field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getString(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::STRING | self::NULL : self::STRING;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns an array field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getArray(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::ARRAY | self::NULL : self::ARRAY;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns an object field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getObject(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::OBJECT | self::NULL : self::OBJECT;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns a boolean, integer, double, or string field.
     *
     * @param string $key The key to locate.
     * @param boolean $nullable Whether or not to include null values.
     * @return self
     */
    public function getScalar(string $key, bool $nullable = false): self
    {
        $type = $nullable ? self::SCALAR | self::NULL : self::SCALAR;
        return $this->getField($key, $type, $nullable);
    }

    /**
     * Returns a JSON field as a string.
     * 
     * For example, using the following structure:
     * 
     * {"test":{"hello":false}}
     * 
     * Using toString() on object "test" will return:
     * 
     * "test": {"hello":false}
     *
     * @return string
     */
    public function toJsonString(bool $prettify = false): string
    {
        $buffer = '';

        if ($this->getKey() !== null) {

            $buffer .= json_encode($this->getKey(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ':';
        }

        $buffer .= $this->toString($prettify);

        return $buffer;
    }

    /**
     * Returns the value as a string, based on the datatype.
     * 
     * For example, using the following structure:
     * 
     * {"test":{"hello":false}}
     * 
     * Using toString() on object "test" will return:
     * 
     * {"hello":false}
     *
     * @return string
     */
    public function toString(bool $prettify = false): string
    {
        // Return if scalar.

        if ($this->isType(self::SCALAR | self::ARRAY | self::OBJECT | self::NULL)) {

            return json_encode($this->getValue(), ($prettify ? JSON_PRETTY_PRINT : 0) | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Unknown datatype, throw error.

        throw new WrongType('Could not handle value with datatype "' . gettype($this->getValue()) . '"');
    }

    /**
     * Adds data to the statistics as determined by the context.
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    public function addContextToStatistics(Statistics $statistics): void
    {
        // Normalize.

        $this->normalizeStatistics($statistics);

        // If the Json has no parent, assume it's the root.

        if ($this->getParent() === null) {

            $this->incrementRoot($statistics);
            return;
        }

        // Key name & value counts.

        $this->incrementKeyValue($statistics);

        // Element counts.

        $this->incrementElementCounts($statistics);

        // Child counts.

        $this->incrementChildCounts($statistics);

        // Datatype counts.

        $this->incrementDatatypes($statistics);
    }

    /**
     * If stats are empty, set some default values in the context of Json.
     *
     * @param Statistics $statistics The statistics to normalize.
     * @return void
     */
    protected function normalizeStatistics(Statistics $statistics): void
    {
        if (empty($statistics->getStatistics())) {
            
            $statistics->setRawStatistics([
                'keys' => [],
                'datatypes' => [],
                'elements' => [
                    'total' => 0
                ],
                'fields' => [
                    'total' => 0
                ],
                'root' => [
                    'datatypes' => [],
                    'children' => 0
                ]
            ]);
        }
    }

    /**
     * Increments root-based data, including datatype and number of
     * children, if applicable.
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    protected function incrementRoot(Statistics $statistics): void
    {
        $typeName = implode('/', JsonUtils::normalizeTypeInteger($this->getType()));

        $statistics->addStat(1, 'root', 'datatypes', $typeName);

        if ($this->isType(Json::ARRAY)) {

            $statistics->statistics['root']['children'] = $this->getElements()->count();
        }

        if ($this->isType(Json::OBJECT)) {

            $statistics->statistics['root']['children'] = $this->getFields()->count();
        }
    }

    /**
     * Increments total key count and relevant values if applicable.
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    protected function incrementKeyValue(Statistics $statistics): void
    {
        $key = json_encode($this->getKey(), JSON_UNESCAPED_SLASHES);

        if ($this->isType(Json::SCALAR)) {

            // If scalar, store total and the actual value.

            $statistics->addStat(1, 'keys', $key, 'scalar', 'total');
            $statistics->addStat(1, 'keys', $key, 'scalar', 'values', $this->toString());
        } else if ($this->isType(Json::OBJECT)) {

            // If object, store total.

            $statistics->addStat(1, 'keys', $key, 'object', 'total');
        } else if ($this->isType(Json::ARRAY)) {

            // If array, store total.

            $statistics->addStat(1, 'keys', $key, 'array', 'total');
        } else if ($this->isType(Json::NULL)) {

            // If null, store total.

            $statistics->addStat(1, 'keys', $key, 'null', 'total');
        } else {

            // If none of the above, unknown datatype.

            $statistics->addStat(1, 'keys', $key, JsonUtils::UNKNOWN_TYPE, 'total');
        }
    }

    /**
     * Add 1 to "datatypes.<type>"
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    protected function incrementDatatypes(Statistics $statistics): void
    {
        $typeName = implode('/', JsonUtils::normalizeTypeInteger($this->getType()));

        $statistics->addStat(1, 'datatypes', $typeName);
    }

    /**
     * If the parent is an array, add 1 to "elements.total"
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    protected function incrementElementCounts(Statistics $statistics): void
    {
        if ($this->getParent() !== null && $this->getParent()->isType(Json::ARRAY)) {

            $statistics->addStat(1, 'elements', 'total');
        }
    }

    /**
     * If the parent is an object, add 1 to "children.total"
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    protected function incrementChildCounts(Statistics $statistics): void
    {
        if ($this->getParent() !== null && $this->getParent()->isType(Json::OBJECT)) {

            $statistics->addStat(1, 'fields', 'total');
        }
    }
}