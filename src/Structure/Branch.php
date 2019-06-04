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
    private $conditions = [];

    /**
     * A branch will be traveled only when the provided condition is true.
     *
     * @param string $branchName The custom name of the branch (not a key), used for error messages.
     * @param Structure $structure The structure to branch to if the condition succeeds.
     * @param \closure ...$conditions The conditions to check.
     */
    public function __construct(string $branchName, Structure $parent, Structure $structure, ICondition ...$conditions)
    {
        $this->branchName = $branchName;
        $this->parent = $parent;
        $this->structure = $structure;
        $this->addConditions(...$conditions);
    }

    /**
     * Adds multiple conditions to the branch.
     * 
     * TODO: use a trait?
     *
     * @param ICondition ...$conditions
     * @return void
     */
    public function addConditions(ICondition ...$conditions): void
    {
        for ($i = 0, $j = count($conditions); $i < $j; $i++) {

            $this->addCondition($conditions[$i]);
        }
    }

    /**
     * Adds a single condition to the branch.
     * 
     * TODO: use a trait?
     *
     * @param ICondition $condition
     * @return void
     */
    public function addCondition(ICondition $condition): void
    {
        $this->conditions[] = $condition;
    }

    /**
     * Checks whether or not the branch is accessible using the provided input.
     *
     * @param \stdClass $json The JSON input to check.
     * @param Reports $reports Error-reporting collection.
     * @return boolean
     */
    public function succeeds(\stdClass $json, Reports $reports): bool
    {
        // If the parent is an array, validate it itself.

        if ($this->parent->getOptions()->isType(JsonUtils::ARRAY)) {

            return $this->validateConditions($json, $reports, $this->parent);
        }
        
        // Otherwise just validate the branch's structure.

        return $this->validateConditions($json, $reports, $this->structure);
    }

    /**
     * Checks all the conditions of the branch to ensure they pass.
     *
     * @param \stdClass $json The JSON input to check against the conditions.
     * @param Reports $reports Error-reporting collection.
     * @param Structure $structure The structure to validate with.
     * @return boolean
     */
    private function validateConditions(\stdClass $json, Reports $reports, Structure $structure): bool
    {
        $succeeds = true;

        // Cycle through each one and collect the necessary reports from all conditions before returning failure.

        for ($i = 0, $j = count($this->conditions); $i < $j; $i++) {

            if (!$this->conditions[$i]->validate($json, $structure, $reports, false)) {

                $succeeds = false;
            }
        }

        // Return whether or not every single condition succeeded.

        return $succeeds;
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
     * Returns the parent structure of this branch.
     *
     * @return Structure
     */
    public function getParent(): Structure
    {
        return $this->parent;
    }

    /**
     * Returns the custom name of this branch.
     *
     * @return string
     */
    public function getBranchName(): string
    {
        return $this->branchName;
    }

    /**
     * Returns all conditions of the branch.
     *
     * @return array
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}