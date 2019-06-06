<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class KeyHasValue implements ICondition
{
    protected $key;
    protected $validValues = [];

    // TODO: merge this and HasValue.
    public function __construct(string $key, ...$validValues)
    {
        $this->key = $key;
        $this->validValues = $validValues;
    }

    /**
     * Checks to ensure that the specified field has a value
     * within the acceptable array of values.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        // The JSON did not contain the structure's key in proper format.

        if (!JsonUtils::hasKey($this->key, $json)) {

            return false;
        }

        // If the value isn't scalar, skip.

        $value = JsonUtils::get($this->key, $json);

        if (!is_scalar($value)) {

            return false;
        }

        // Validate the value against all valid values.

        if (!in_array($value, $this->validValues)) {

            if ($announce) {

                $reports->addWarning('Value "' . $value . '" is not valid. Must be one of: <code>' . implode('</code>, <code>', $this->validValues) . '</code>');
            }
            
            return false;
        }

        // Otherwise all good.

        return true;
    }
}