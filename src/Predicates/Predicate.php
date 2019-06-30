<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\IPredicate;
use Celestriode\JsonUtils\TMultiSingleton;
use Celestriode\JsonUtils\Structure\Report;

abstract class Predicate implements IPredicate
{
    use TMultiSingleton;

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report
    {
        return Report::warning('Predicate failed');
    }
}