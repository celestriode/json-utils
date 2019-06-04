<?php namespace Celestriode\JsonUtils\Structure;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Conditions\ICondition;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class Branch
{
    private $branchName;
    private $parent;
    private $structure;
    private $condition;

    /**
     * A branch will be traveled only when the provided condition is true.
     *
     * @param string $branchName The custom name of the branch (not a key), used for error messages.
     * @param Structure $structure The structure to branch to if the condition succeeds.
     * @param \closure $condition The condition to check.
     */
    public function __construct(string $branchName, Structure $parent, Structure $structure, ICondition $condition)
    {
        $this->branchName = $branchName;
        $this->parent = $parent;
        $this->structure = $structure;
        $this->condition = $condition;
    }

    /**
     * Checks whether or not the branch is accessible using the provided input.
     *
     * @param \stdClass $json The JSON input to check against the condition.
     * @param Reports $reports Error-reporting collection.
     * @return boolean
     */
    public function succeeds(\stdClass $json, Reports $reports): bool
    {
        // If the parent is an array, validate it itself.

        if ($this->parent->getOptions()->isType(JsonUtils::ARRAY)) {

            return $this->condition->validate($json, $this->parent, $reports, false);
        }
        
        // Otherwise just validate the branch's structure.

        return $this->condition->validate($json, $this->structure, $reports, false);
    }

    /**
     * Returns the structure that this branch validates JSON with.
     *
     * @return Structure
     */
    public function getStructure(): Structure
    {
        return $this->structure;
    }

    /**
     * Returns the custom name of the branch.
     *
     * @return string
     */
    public function getBranchName(): string
    {
        return $this->branchName;
    }
}