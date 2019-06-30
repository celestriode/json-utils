<?php namespace Celestriode\JsonUtils\Structure\Audits;

use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\Predicates;

class HasValue extends Audit
{
    protected $values = [];

    public function __construct(...$values)
    {
        $this->values = $values;
    }

    /**
     * Checks if the incoming Json is one of the specified values.
     * If not, adds a warning to the reports.
     *
     * @param Structure $structure The structure at the current depth.
     * @param Json $json The Json at the current depth.
     * @param Reports $reports Reports at the current depth.
     * @return void
     */
    public function audit(Structure $structure, Json $json, Reports $reports): void
    {
        $value = $json->getValue();
        $predicate = new Predicates\HasValue(...$this->values);

        if (!$predicate->test($json)) {
            
            $reports->addReport($predicate->getReport());
        }
    }
}