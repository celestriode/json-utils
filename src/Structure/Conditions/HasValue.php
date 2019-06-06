<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;
use Celestriode\Exception;

class HasValue implements ICondition
{
    protected $validValues = [];

    public function __construct(...$validValues)
    {
        $this->validValues = $validValues;
    }

    /**
     * Adds scalar values to the list of acceptable values.
     *
     * @param mixed ...$values
     * @return void
     */
    private function addValues(...$values): void
    {
        for ($i = 0, $j = count($values); $i < $j; $i++) {

            if (!is_scalar($value)) {

                throw new Exception\BadStructure('Valid values must all be scalar, input was instead of type "' . gettype($value) . '".');
            }
    
            $this->validValues[] = $value;
        }
    }

    /**
     * Checks if the current structure's scalar value is equal to the provided input.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        // The JSON did not contain the structure's key in proper format, skip.

        if (!JsonUtils::hasKey($structure->getKey(), $json) || !is_scalar(JsonUtils::get($structure->getKey(), $json))) {
            
            return false;
        }

        // Validate the value against all valid values.

        $value = JsonUtils::getScalar($structure->getKey(), $json);

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