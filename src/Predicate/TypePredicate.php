<?php namespace Celestriode\JsonUtils\Predicate;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

abstract class TypePredicate extends Predicate
{
    protected $error = 'Found key "%s" but it was of type "%s" instead of the expected type "%s"';

    protected $key;
    protected $type;
    protected $expectedTypeString;

    /**
     * A predicate specific to data types. Used when getting data out of a JSON object.
     *
     * @param string $key The key of the value being tested.
     * @param string $type The actual data type of the value being tested.
     * @param string $expectedTypeString The expected data type of the value being tested.
     */
    public function __construct(string $key, string $type = 'unknown', string $expectedTypeString = 'unknown')
    {
        $this->key = $key;
        $this->type = $type;
        $this->expectedTypeString = $expectedTypeString;
    }

    /**
     * Returns the human-friendly name of the expected datatype for the value.
     *
     * @return string
     */
    public function getExpectedTypeString(): string
    {
        return $this->expectedTypeString;
    }

    /**
     * Returns a custom error should the predicate fail.
     *
     * @return string
     */
    public function getError(): string
    {
        return sprintf($this->error, $this->key, $this->type, $this->expectedTypeString);
    }
}