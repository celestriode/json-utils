<?php namespace Celestriode\JsonUtils;

use Celestriode\JsonUtils\Exception\NotFound;

class JsonCollection extends Json implements \Countable
{
    private $collection = [];

    /**
     * Hosts multiple Json objects together.
     *
     * @param string $raw The raw string before being decoded.
     * @param string $key The key of the value, if applicable. Not applicable for root structures or array elements.
     * @param mixed $value The decoded JSON value itself.
     */
    public function __construct(string $key = null, $value = null, Json ...$collection)
    {
        $this->collection = $collection;
        $this->setKey($key);
        $this->setValue($value);
    }

    /**
     * Sets the decoded JSON, whatever datatype it may be.
     * 
     * For the collection, only does so if not already set.
     *
     * @param mixed $value The decoded JSON value.
     * @return void
     */
    public function setValue($value = null): void
    {
        if (parent::getValue() === null) {

            parent::setValue($value);
        }
    }

    /**
     * Sets the datatype of the JSON input.
     * 
     * For the collection, only does so if not already set.
     *
     * @param integer $type
     * @return void
     */
    public function setType(int $type): void
    {
        if (parent::getType() === null) {

            parent::setType($type);
        }
    }

    /**
     * Returns the collection of Json objects.
     *
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }

    /**
     * Returns all elements within the array as Json classes.
     *
     * @param integer $type The datatype of elements to get.
     * @return array
     */
    public function getElements(int $type = self::ANY): self
    {
        // Just return the collection if there's no extra options.

        if ($type === self::ANY) {

            return $this;
        }

        // Cycle through all elements and create a new JSON class from them.

        $raw = [];
        $collection = [];

        for ($i = 0, $j = count($this->getCollection()); $i < $j; $i++) {

            $current = $this->getElement($i);
            $actualType = $current->getType();

            // Skip if the element's type wasn't the type we're looking for.

            if (($actualType & $type) === 0) {
    
                continue;
            }

            $raw[] = $current->getValue();
            $collection[] = $current;
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
    public function getElement(int $index): Json
    {
        // Throw if index is out of range.

        if (!isset($this->getCollection()[$index])) {

            throw new NotFound('Index ' . $index . ' does not exist within collection.');
        }

        // Return the Json object.

        return $this->getCollection()[$index];
    }

    /**
     * Checks each Json object in the collection to ensure that
     * each one passes the defined predicate. For each one that
     * does pass, the provided function will run.
     * 
     * Returns the number of elements that passed the predicate.
     *
     * @param IPredicate $predicate The predicate to test the Json objects against.
     * @param \closure $func The function to run for predicates that succeed.
     * @return boolean
     */
    public function checkJson(IPredicate $predicate, \closure $func = null): int
    {
        $passed = 0;

        // Cycle through each element, add success.

        for ($i = 0, $j = count($this->getCollection()); $i < $j; $i++) {

            $passed += $this->getCollection()[$i]->checkJson($predicate, $func);
        }

        // Return the number of elements that passed the predicate.

        return $passed;
    }

    /**
     * Returns the number of items in the collection.
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->getCollection());
    }
}