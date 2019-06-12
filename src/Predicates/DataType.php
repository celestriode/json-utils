<?php namespace Celestriode\JsonUtils\Predicates;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\JsonUtils;

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
    public function getError(): string
    {
        return 'Datatype of input must be "' . implode(', ', JsonUtils::normalizeTypeInteger($this->datatype)) . '" instead of "' . implode(', ', JsonUtils::normalizeTypeInteger($this->json->getType())) . '"';
    }
}