<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Report;

class HasValue extends Predicate
{
    protected $values = [];

    public function __construct(...$values)
    {
        $this->values = $values;
    }

    /**
     * Returns true if the incoming Json is one of the specified values.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        $value = $json->getValue();

        // If the value isn't scalar or isn't a correct value, bad.

        if (!is_scalar($value) || !in_array($value, $this->values)) {
            
            return false;
        }

        return true;
    }

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report
    {
        return Report::warning('Field can only have one of the following values: %s', Report::value(...$this->values));
    }
}