<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class FailWithMessage implements ICondition
{
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * A condition that always fails and will add a fatal error
     * to the report with the supplied message.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return boolean
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        if ($announce) {

            $reports->addFatal($this->message);
        }

        // Always return false for this one.

        return false;
    }
}