<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class AtLeastOneValidKey implements ICondition
{
    /**
     * Ensures that the object contains at least one of the structure's valid
     * keys. If it contains no valid keys, an error is reported.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        $validKeys = $structure->getValidKeys();

        // Cycle through all valid keys.

        for ($i = 0, $j = count($validKeys); $i < $j; $i++) {

            // If the valid key exists within the structure, success and end.

            if (JsonUtils::hasKey($validKeys[$i], $json)) {

                return true;
            }
        }

        // Did not end early, therefore all valid keys were absent.

        if ($announce) {

            $reports->addFatal('You must have at least one of the following keys: ' . implode(', ', $validKeys));
        }

        return false;
    }
}