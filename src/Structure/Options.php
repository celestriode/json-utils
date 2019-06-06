<?php namespace Celestriode\JsonUtils\Structure;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\JsonUtils;
use Celestriode\JsonUtils\Predicate\MultiTypePredicate;

class Options
{
    private $type;
    private $required;
    private $isPlaceholder;

    protected $empty = false;

    protected $conditions = [];
    protected $branches = [];

    /**
     * Creates some options for the structure.
     * 
     * Specifies the datatype of the structure. When not set to ANY, it will error
     * when the datatype does not match. THIS IS A BITFIELD; e.g. use INT | DOUBLE if
     * it can be either.
     * 
     * Specifies whether or not the structure is required.
     *
     * @param integer $type The datatype of the structure.
     * @param boolean $required Whether or not the structure must exist within its parent.
     * @param boolean $placeholder Whether or not the structure's key can be anything at all.
     */
    public function __construct(int $type = JsonUtils::ANY, bool $required = true, bool $isPlaceholder = false)
    {
        $this->type = $type;
        $this->required = $required;
        $this->isPlaceholder = $isPlaceholder;
    }

    /**
     * Sets the datatype of the structure in numerical format.
     * 
     * Use JsonUtils::<TYPE> for the correct numbers.
     *
     * @param integer $type T
     * @return self
     */
    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Marks the structure as being required.
     *
     * @param boolean $required True if required.
     * @return self
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Marks the structure as being a placeholder, in which the key can be anything
     * for this particular structure.
     *
     * @param boolean $isPlaceholder True if placeholder.
     * @return self
     */
    public function setIsPlaceholder(bool $isPlaceholder): self
    {
        $this->isPlaceholder = $isPlaceholder;

        return $this;
    }

    /**
     * Marks the structure as being empty and thus should not be validated at all.
     *
     * @param boolean $empty True if empty.
     * @return self
     */
    public function setEmpty(bool $empty): self
    {
        $this->empty = $empty;

        return $this;
    }

    /**
     * Adds a condition to the structure.
     *
     * @param Conditions\ICondition $condition The condition to add.
     * @return self
     */
    public function addCondition(Conditions\ICondition $condition): self
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * Runs the stored conditions to handle custom errors.
     * 
     * Returns false if any one condition fails.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The structure at the current depth.
     * @param Reports $reports Reports to add to.
     * @return bool
     */
    public function checkConditions(\stdClass $json, Structure $structure, Reports $reports): bool
    {
        $success = true;

        for ($i = 0, $j = count($this->conditions); $i < $j; $i++) {

            $condition = $this->conditions[$i];

            if (!$condition->validate($json, $structure, $reports)) {

                $success = false;
            }
        }

        return $success;
    }

    /**
     * Cycles through each branch and validates them provided their condition succeeds.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $parent The parent structure to pass on to the branch.
     * @param Reports $reports Reports to add to.
     * @return void
     */
    public function validateBranches(\stdClass $json, Structure $parent, Reports $reports): bool
    {
        $succeeds = false;

        foreach ($this->branches as $name => $branch) {

            // If the branch's conditions succeed...

            if ($branch->succeeds($json, $reports)) {

                $succeeds = true;

                // Add info about successful branching.

                if (isset($json->{$parent->getKey()})) {

                    $reports->addInfo('Successfully branched to "' . $branch->getBranchName() . '" with value "' . JsonUtils::toString($json->{$parent->getKey()}) . '"');
                } else {

                    $reports->addInfo('Successfully branched to "' . $branch->getBranchName() . '"');

                }
                
                // If this branch belongs to an array, validate it differently.

                if ($parent->getOptions()->isType(JsonUtils::ARRAY)) {

                    // Create a buffer structure to fix the data structures for automatic validation.

                    $bufferStructure = Structure::root(
                        $branch->getStructure()
                    );
                    $bufferJson = new \stdClass();
                    $bufferJson->{$branch->getStructure()->getKey()} = $json->{$parent->getKey()};

                    // Validate the buffer structure.

                    $bufferStructure->compare($bufferJson, $reports, $parent);
                } else {

                    // Add the structure of the branch to the parent for deferred validation.

                    $parent->addChild($branch->getStructure());
                }
            }
        }

        // Return whether or not any branch succeeded.

        return $succeeds;
    }

    /**
     * Validates whether or not the structure contains a key with the expected type.
     * 
     * Handles error reporting itself and does not return anything.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The structure at the current depth.
     * @param Reports $reports Reports to add to.
     * @return void
     */
    public function validateType(\stdClass $json, string $key, Reports $reports): void
    {
        // Skip if there's no key to find or if type is intended to be any.

        if (!JsonUtils::hasKey($key, $json) || $this->type === JsonUtils::ANY) {

            return;
        }

        // Get the key itself, let JsonUtils handle the error throwing.

        JsonUtils::get($key, $json, new MultiTypePredicate($key, gettype($json->{$key} ?? null), $this->type));
    }

    /**
     * Adds multiple branches.
     *
     * @param Branch ...$branches The branches to add.
     * @return void
     */
    public function addBranches(Branch ...$branches): void
    {
        for ($i = 0, $j = count($branches); $i < $j; $i++) {

            $this->addBranch($branches[$i]);
        }
    }

    /**
     * Adds a single branch.
     *
     * @param Branch $branch The branch to add.
     * @return void
     */
    public function addBranch(Branch $branch): void
    {
        $this->branches[$branch->getBranchName()] = $branch;
    }

    /**
     * Returns the expected datatype (bitfield).
     *
     * @return integer
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * Checks whether or not the input type is within the bitfield of
     * expected types of this structure.
     *
     * @param integer $type The type to compare with.
     * @return boolean
     */
    public function isType(int $type): bool
    {
        return ($this->type & $type) !== 0;
    }

    /**
     * Returns whether or not this structure is required.
     *
     * @return boolean
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Returns whether or not the key should be ignored in favor of
     * accepting all keynames within the current object.
     *
     * @return boolean
     */
    public function isPlaceholder(): bool
    {
        return $this->isPlaceholder;
    }

    /**
     * Returns whether or not this structure is empty.
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }
}