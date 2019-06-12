<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;

final class AlwaysTrue extends Predicate
{
    /**
     * Will only return true.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        return true;
    }
}