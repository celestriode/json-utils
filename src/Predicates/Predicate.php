<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\IPredicate;
use Celestriode\JsonUtils\TMultiSingleton;

abstract class Predicate implements IPredicate
{
    use TMultiSingleton;

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getError(): string
    {
        return 'Predicate failed';
    }

    /**
     * Turns an array of strings into code-encompassed, comma-separated strings.
     *
     * @param string ...$values The values to condense.
     * @return string
     */
    protected function normalizeValues(string ...$values): string
    {
        return '<code>' . implode('</code>, <code>', $values) . '</code>';
    }
}