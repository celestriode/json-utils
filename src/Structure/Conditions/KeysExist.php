<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class KeysExist implements ICondition
{
    private $keys = [];

    public function __construct(string ...$keys)
    {
        $this->keys = $keys;
    }

    /**
     * Checks the list of keys that have to exist as siblings
     * within this structure to ensure they exist within JSON.
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

        // Go through all required keys and check the JSON for them. Add them to array if not present.

        for ($i = 0, $j = count($this->keys); $i < $j; $i++) {

            if (!JsonUtils::hasKey($this->keys[$i], $json)) {

                $keys[] = $this->keys[$i];
            }
        }

        // If the JSON did not contain any of them, bad. Array contains the missing keys.

        if (count($keys) > 0) {

            if ($announce) {

                $reports->addFatal('You must have one of the following keys: <code>' . implode('</code>, <code>', $this->keys) . '</code>');
            }

            return false;
        }

        // Otherwise all good, return true.

        return true;
    }
}