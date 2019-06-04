<?php namespace Celestriode\JsonUtils\Predicate;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\JsonUtils;

class MultiTypePredicate extends TypePredicate
{
    /**
     * A predicate specific to data types. Used when getting data out of a JSON object.
     *
     * @param string $key The key of the value being tested.
     * @param string $type The actual data type of the value being tested.
     * @param string $expectedTypes The expected data type of the value being tested.
     */
    public function __construct(string $key, string $type = 'unknown', int $expectedTypes = JsonUtils::ANY)
    {
        $this->key = $key;
        $this->type = $type;
        $this->expectedTypeString = implode(', ', JsonUtils::normalizeTypeInteger($expectedTypes));
        $this->expectedTypes = $expectedTypes;
    }

    public function test($value): bool
    {
        // Automatically true if the allowed type can be anything.
        
        if ($this->expectedTypes === JsonUtils::ANY) {

            return true;
        }

        // Otherwise check if the type is valid.
        
        return ($this->expectedTypes & JsonUtils::normalizeTypeString($this->type)) !== 0;
    }
}