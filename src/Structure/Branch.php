<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\IPredicate;
use Celestriode\JsonUtils\Json;

class Branch
{
    protected $label;
    protected $structure;
    protected $predicates = [];

    public function __construct(string $label, Structure $structure, IPredicate ...$predicates)
    {
        $this->setLabel($label);
        $this->setStructure($structure);
        $this->addPredicates(...$predicates);
    }

    /**
     * Sets the label of the branch for user-friendly errors.
     *
     * @param string $label The label of the branch.
     * @return void
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * Sets the structure to use should this branch succeed.
     *
     * @param Structure $structure The structure of the branch.
     * @return void
     */
    public function setStructure(Structure $structure): void
    {
        $this->structure = $structure;
    }

    /**
     * Adds multiple predicates that all must succeed in order
     * to use this branch's structure.
     *
     * @param IPredicate ...$predicates The predicates that must succeed.
     * @return void
     */
    public function addPredicates(IPredicate ...$predicates): void
    {
        for ($i = 0, $j = count($predicates); $i < $j; $i++) {

            $this->addPredicate($predicates[$i]);
        }
    }

    /**
     * Adds a single predicate that must succeed in order to
     * use this branch's structure.
     *
     * @param IPredicate $predicate
     * @return void
     */
    public function addPredicate(IPredicate $predicate): void
    {
        $this->predicates[] = $predicate;
    }

    /**
     * Returns the user-friendly label of this branch.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Returns the structure that this branch uses.
     *
     * @return Structure
     */
    public function getStructure(): Structure
    {
        return $this->structure;
    }

    /**
     * Returns all the predicates of this branch.
     *
     * @return array
     */
    public function getPredicates(): array
    {
        return $this->predicates;
    }

    /**
     * Checks if all the predicates succeed based on the incoming Json.
     *
     * @param Json $json The Json to check predicates against.
     * @return boolean
     */
    public function test(Json $json): bool
    {
        $succeeds = true;

        // Cycle through all predicates.

        for ($i = 0, $j = count($this->getPredicates()); $i < $j; $i++) {

            // Test the predicate. If it fails, don't bother checking the rest.

            if (!$this->getPredicates()[$i]->test($json)) {

                $succeeds = false;
                break;
            }
        }

        // Return whether or not all predicates succeeded.

        return $succeeds;
    }
}