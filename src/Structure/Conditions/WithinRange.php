<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class WithinRange implements ICondition
{
    protected $min;
    protected $max;

    public function __construct(float $min = null, float $max = null)
    {
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Checks to ensure that the numeric value is a number between
     * the specified range. If min or max are null, no limit.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return boolean
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        $value = JsonUtils::getScalar($structure->getKey(), $json);

        if (!is_numeric($value) || ($this->min !== null && $value < $this->min) || ($this->max !== null && $value > $this->max)) {

            if ($announce) {

                $reports->addWarning('Value "' . $value . '" is out of range; must be a number between ' . ($this->min ?? '-infinity') . ' and ' .  ($this->max ?? '+infinity'));
            }

            return false;
        }

        return true;
    }
}