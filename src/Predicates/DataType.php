<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\JsonUtils;
use Celestriode\JsonUtils\Structure\Report;

class DataType extends Predicate
{
    protected $datatype;
    protected $json;

    public function __construct(int $datatype)
    {
        $this->datatype = $datatype;
    }

    /**
     * Returns true if the incoming Json's datatype matches the stored datatype.
     *
     * @param Json $json The Json to test with.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        $this->json = $json;

        return $json->isType($this->datatype);
    }

    /**
     * Returns a helpful error message to optionally use if the predicate fails.
     *
     * @return string
     */
    public function getReport(): Report
    {
        if ($this->json === null) {

            return Report::warning('Datatype of input must be %s', Report::value(...JsonUtils::normalizeTypeInteger($this->datatype)));
        }

        return Report::warning('Datatype of input me be %s instead of %s', Report::value(...JsonUtils::normalizeTypeInteger($this->datatype)), Report::value(...JsonUtils::normalizeTypeInteger($this->json->getType())));
    }
}