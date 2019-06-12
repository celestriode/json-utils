<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;

final class AlwaysFalse extends Predicate
{
    /**
     * Will only return false.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        return false;
    }
}