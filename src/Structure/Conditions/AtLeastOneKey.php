<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class AtLeastOneKey implements ICondition
{
    /**
     * Ensures that the object contains at least one key whatsoever.
     * 
     * Does not check if the key itself is valid.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        if ($announce && empty((array)$json)) {

            $reports->addFatal('You must have at least one key within "' . $structure->getKey() . '"');
        }

        return !empty((array)$json);
    }
}