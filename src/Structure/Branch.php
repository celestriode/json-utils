<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\IPredicate;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Exception;

class Branch
{
    protected $label;
    protected $structures = [];
    protected $predicates = [];

    public function __construct(string $label, IPredicate $predicate, Structure ...$structures)
    {
        $this->setLabel($label);
        $this->addStructures(...$structures);
        $this->addPredicate($predicate);
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
     * Adds multiple structures to use should this branch succeed.
     *
     * @param Structure ...$structure The structure of the branch.
     * @return void
     */
    public function addStructures(Structure ...$structures): void
    {
        for ($i = 0, $j = count($structures); $i < $j; $i++) {

            $this->addStructure($structures[$i]);
        }
    }

    /**
     * Adds a single structure to use should this branch succeed.
     *
     * @param Structure $structure The structure of the branch.
     * @return void
     */
    public function addStructure(Structure $structure): void
    {
        if ($structure->getKey() === null && !$structure->getOptions()->branches()) {

            // If the branch's structure doesn't have a key, the structure is simply invalid.

            throw new Exception\BadStructure('A branch\'s structure cannot have a null key');
        }

        $this->structures[] = $structure;
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
     * Returns the structures that this branch uses.
     *
     * @return Structure
     */
    public function getStructures(): array
    {
        return $this->structures;
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
     * Goes through all of the structures in the branch and sets
     * their parent as the incoming structure.
     *
     * @param Structure $parent The parent of all the structures in the branch.
     * @return void
     */
    public function setParentOfStructures(Structure $parent): void
    {
        foreach ($this->getStructures() as $structure) {

            $structure->setParent($parent);
        }
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

    /**
     * Compares the incoming Json to the branch's expected structure.
     * 
     * Returns an array of valid keys as discovered in the branch.
     *
     * @param Json $json The Json to compare with.
     * @param Reports $reports Reports to add to.
     * @return array
     */
    public function compare(Json $json, Reports $reports): array
    {
        $keys = [];

        /** @var Structure $structure */
        foreach ($this->getStructures() as $structure) {

            // Skip if the key is null when it shouldn't be.

            if ($structure->getKey() === null && !$structure->getOptions()->branches()) {

                continue;
            }

            // If it's a branch, do that.

            if ($structure->getOptions()->branches()) {
                
                if ($structure->getBranch()->test($json)) {

                    $reports->addReport(Report::info('Successfully branched to %s', Report::key($structure->getBranch()->getLabel())));

                    $keys = array_merge($keys, $structure->getBranch()->compare($json, $reports));
                }
            } else {

                $keys[] = $structure->getKey();

                // Otherwise just compare as a regular structure.
        
                if (!$json->hasField($structure->getKey())) {

                    // If the branch's structure didn't exist when it needed to, throw error.

                    if ($structure->getOptions()->isRequired()) {

                        $reports->addReport(Report::fatal('Missing required key %s for branch %s', Report::key($structure->getKey()), Report::key($this->getLabel())));
                    }

                    continue;
                }

                $field = $json->getField($structure->getKey());

                $structure->compare($field, $reports->createChildReport($field, $structure->getKey()));
            }
        }

        return $keys;
    }
}