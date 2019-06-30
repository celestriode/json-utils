<?php namespace Celestriode\JsonUtils;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Report;

interface IPredicate
{
    /**
     * Performs a test against the Json object. Returns true if
     * the test succeeds.
     * 
     * These are similar to audits in terms of testing, but cannot
     * be directly used for actually reporting the errors despite
     * holding the error strings.
     * 
     * These can be used to check Json structures manually without
     * making use of the library's "structure" feature.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool;

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report;
}