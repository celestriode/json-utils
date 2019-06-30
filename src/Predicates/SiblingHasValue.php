<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Report;

class SiblingHasValue extends HasValue
{
    protected $sibling;

    public function __construct(string $sibling, ...$values)
    {
        $this->sibling = $sibling;
        parent::__construct(...$values);
    }

    /**
     * Checks if the incoming Json has a sibling with
     * the provided value. Useful for branching based
     * on values of other fields.
     *
     * @param Json $json The incoming Json. TODO: determine if it's already the parent?
     * @return boolean
     */
    public function test(Json $json): bool
    {
        $parent = $json;

        // If the parent exists and has the expected field, check it via HasValue.
        
        if ($parent !== null && $parent->hasField($this->sibling)) {

            return parent::test($parent->getField($this->sibling));
        }

        return false;
    }

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report
    {
        return Report::warning('Sibling %s can only have one of the following values: %s', Report::key($this->sibling), Report::value(...$this->values));
    }
}