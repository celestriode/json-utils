<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;

interface ICondition
{
    /**
     * Preset conditions are for standardizing error reporting.
     * 
     * Use these whenever they are needed, or create your own.
     * 
     * Or don't.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param bool $announce Whether or not to add errors to reports.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool;
}