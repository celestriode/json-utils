<?php namespace Celestriode\JsonUtils\Predicate;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

abstract class Predicate
{
    protected $error = 'Predicate failed';

    /**
     * Performs a test against a value to determine if the predicate succeeds.
     *
     * @param mixed $value The value to test with.
     * @return boolean
     */
    abstract public function test($value): bool;

    /**
     * Returns a custom error should the predicate fail.
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }
}