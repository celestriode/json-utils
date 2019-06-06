<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class CannotHaveValue extends HasValue
{
    /**
     * Checks if the HasValue condition succeeds. In which case,
     * this condition fails.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return boolean
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        if (parent::validate($json, $structure, $reports, false)) {

            if ($announce) {

                $reports->addWarning('Value of <code>' . $structure->getKey() .  '</code> cannot be any of the following: <code>' . implode('</code>, <code>', $this->validValues) . '</code>');
            }

            return false;
        }

        return true;
    }
}