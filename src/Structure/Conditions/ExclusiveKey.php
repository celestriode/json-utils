<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class ExclusiveKey implements ICondition
{
    private $atLeastOneRequired;
    private $keys = [];

    public function __construct(bool $atLeastOneRequired, string ...$keys)
    {
        $this->atLeastOneRequired = $atLeastOneRequired;
        $this->keys = $keys;
    }

    /**
     * Specifies a list of keys that cannot co-exist as siblings.
     * 
     * If $atLeastOneRequired is true, then one of these keys must exist.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return boolean
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        $keys = [];

        // Go through all exclusive keys and check the JSON for them.

        for ($i = 0, $j = count($this->keys); $i < $j; $i++) {

            if (JsonUtils::hasKey($this->keys[$i], $json)) {

                $keys[] = $this->keys[$i];
            }
        }

        // If the JSON did not contain any of them and at least one is required, bad.

        if (count($keys) === 0 && $this->atLeastOneRequired) {

            if ($announce) {

                $reports->addFatal('You must have one of the following keys: <code>' . implode('</code>, <code>', $this->keys) . '</code>');
            }

            return false;
        }

        // If there are conflicting keys, bad.

        if (count($keys) > 1) {

            if ($announce) {

                $reports->addFatal('Conflicting keys specified: <code>' . implode('</code>, <code>', $keys) . '</code>. You cannot have more than one of the following keys together: <code>' . implode('</code>, <code>', $this->keys) . '</code>');
            }

            return false;
        }

        // Otherwise all good, return true.

        return true;
    }
}