<?php namespace Celestriode\JsonUtils;

use Celestriode\JsonUtils\Structure\Options;
use Celestriode\JsonUtils\Structure\OptionsBuilder;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\Structure\Branch;
use Celestriode\JsonUtils\Structure\Report;
use Ramsey\Uuid\UuidInterface;

class Structure
{
    protected static $uuids = [];

    protected $key;
    protected $options;
    protected $children = [];
    protected $elements = [];

    protected $audits = [];
    protected $branch;
    protected $requiredBranchCount = [null, null];
    protected $uuid;

    protected $parent;

    public function __construct(string $key = null, Options $options = null, self ...$children)
    {
        $this->setKey($key);
        $this->setOptions($options ?? new Options());
        $this->addChildren(...$children);
    }

    /**
     * Sets the key of this field, where applicable.
     *
     * @param string $key The key of the field.
     * @return void
     */
    public function setKey(string $key = null): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Returns the key of this structure.
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Sets the custom options of this structure.
     *
     * @param Options $options The options of the structure.
     * @return void
     */
    public function setOptions(Options $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns the custom options of this structure.
     *
     * @return Options
     */
    public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * States that the branch must have between X and Y number of children
     * that successfully branch.
     *
     * @param integer $min The minimum number of times the structure can branch.
     * @param integer $max The maximum number of times the structure can branch.
     * @return self
     */
    public function setMustBranch(int $min = 1, int $max = null): self
    {
        $this->requiredBranchCount = [$min, $max];

        return $this;
    }

    /**
     * Get the min/max number of times the structure may branch.
     *
     * @return array
     */
    public function getRequiredBranches(): array
    {
        return $this->requiredBranchCount;
    }

    /**
     * Adds multiple children to the structure.
     *
     * @param self ...$children The children to add.
     * @return void
     */
    public function addChildren(self ...$children): self
    {
        for ($i = 0, $j = count($children); $i < $j; $i++) {

            $this->addChild($children[$i]);
        }

        return $this;
    }

    /**
     * Adds a child to the structure.
     *
     * @param self $child The child to add.
     * @return void
     */
    public function addChild(self $child): self
    {
        // Throw if the structure isn't an object.

        if (!$this->getOptions()->isExpectedType(Json::OBJECT)) {

            throw new Exception\BadStructure('Cannot add children to non-object structures');
        }

        // Otherwise add the child.

        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Adds multiple elements to the structure.
     *
     * @param self ...$elements The elements to add.
     * @return void
     */
    public function addElements(self ...$elements): self
    {
        for ($i = 0, $j = count($elements); $i < $j; $i++) {

            $this->addElement($elements[$i]);
        }

        return $this;
    }

    /**
     * Adds an element to the structure.
     *
     * @param self $element The element to add.
     * @return void
     */
    public function addElement(self $element): self
    {
        // Throw if the structure isn't an array.

        if (!$this->getOptions()->isExpectedType(Json::ARRAY)) {

            throw new Exception\BadStructure('Cannot add children to non-object structures');
        }

        // Otherwise add the element.

        $this->elements[] = $element;
        $element->setParent($this);

        return $this;
    }

    /**
     * Sets the parent structure of this structure.
     *
     * @param self $parent The parent of the structure.
     * @return self
     */
    public function setParent(self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Returns the parent of the structure, which implies that the
     * parent is an object or an array. Null if no parent (which
     * typically means it's the root).
     *
     * @return self|null
     */
    public function getParent(): ?self
    {
        return $this->parent;
    }

    /**
     * Returns all the children of this structure.
     *
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Returns all the elements of this structure.
     *
     * @return array
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Adds an audit to the structure.
     * 
     * Use audits to validate the values incoming Json.
     * 
     * Predicates may be added to prevent an audit from occurring without first
     * passing certain tests.
     *
     * @param IAudit $audit The audit to add.
     * @param IPredicate ...$predicates The predicates that must succeed before auditing can be done.
     * @return self
     */
    public function addAudit(IAudit $audit, IPredicate ...$predicates): self
    {
        $this->audits[] = [
            'audit' => $audit,
            'predicates' => $predicates
        ];

        return $this;
    }

    /**
     * Returns all the audits stored in this structure.
     *
     * @return array
     */
    public function getAudits(): array
    {
        return $this->audits;
    }

    /**
     * Sets the structure's branch.
     *
     * @param Branch $branch The branch to traverse if possible.
     * @return self
     */
    public function setBranch(Branch $branch): self
    {
        $this->branch = $branch;
        $branch->setParentOfStructures($this);

        return $this;
    }

    /**
     * Returns the branch of the structure.
     *
     * @return Branch
     */
    public function getBranch(): ?Branch
    {
        return $this->branch;
    }

    /**
     * Compares incoming Json with the structure to determine validity.
     * 
     * If a Reports object is not supplied, a new one is created.
     *
     * @param Json $json The incoming Json to compare with the structure.
     * @param Reports $reports Reports to add to.
     * @return Reports
     */
    public function compare(Json $json, Reports $reports = null): Reports
    {
        if ($reports === null) {

            // If reports wasn't specified, create a new one to move around.

            $reports = new Reports($json, $this->getKey());
        } else if ($reports->getJson() === null) {

            // If Json was not specified, give it the Json that was already supplied.

            $reports->setJson($json);
        }

        $this->checkStructure($json, $reports);

        return $reports;
    }

    /**
     * Goes through the structure and determines its validity.
     * 
     * If there are any issues or information that should be
     * conveyed, it will do so via the supplied reports.
     *
     * @param Json $json The incoming Json to compare with the structure.
     * @param Reports $reports Reports to add to.
     * @return void
     */
    protected function checkStructure(Json $json, Reports $reports): void
    {
        // Cycle through all audits. These come first in order to let them manipulate other checks.

        foreach ($this->getAudits() as $audit) {

            $succeeds = true;

            // Cycle through the audit's predicates, if available.

            for ($i = 0, $j = count($audit['predicates']); $i < $j; $i++) {

                // If the predicate fails, say so and stop the loop.

                if (!$audit['predicates'][$i]->test($json)) {

                    $succeeds = false;
                    break;
                }
            }

            // If every single predicate succeeded, perform the audit.
            
            if ($succeeds) {

                $audit['audit']->audit($this, $json, $reports);
            }
        }

        // Ignore this structure if it's been marked as such by an audit.

        if ($this->getOptions()->skip()) {

            return;
        }

        // Check if it uses an ancestor or redirects.

        if ($this->getOptions()->redirects() || $this->getOptions()->usesAncestor()) {

            // Redirecting takes precedence over ascending.

            $ancestor = $this->getOptions()->redirects() ? clone self::getStructureFromUuid($this->getOptions()->getRedirect()) : clone $this->findAncestor($this->getOptions()->getAncestor());

            // Set the target's key to the current structure's key to prevent "invalid key" issue. If there wasn't a target an error is already thrown.

            $ancestor->setKey($this->getKey());

            // Compare and do not continue validating this structure.

            $ancestor->compare($json, $reports);
            return;
        }

        // Verify keys.

        if ($this->getKey() !== $json->getKey() && ($json->getParent() === null || ($json->getParent() !== null && !$json->getParent()->isType(Json::ARRAY)))) {

            $reports->addReport(Report::fatal('Key %s does not match expected key %s', Report::key($json->getKey()), Report::key($this->getKey())));
        }

        // Verify datatype.

        if (!$this->getOptions()->isExpectedType($json->getType())) {

            $needs = implode(', ', JsonUtils::normalizeTypeInteger($this->getOptions()->getExpectedType()));
            $has = implode(', ', JsonUtils::normalizeTypeInteger($json->getType()));

            if ($json->getKey() !== null) {

                $reports->addReport(Report::fatal(
                    'Incorrect datatype for field %s with value %s (expected %s, was %s)',
                    Report::key($json->getKey()),
                    Report::value($json->toString()),
                    Report::value($needs),
                    Report::value($has)
                ));
            } else {

                $reports->addReport(Report::fatal(
                    'Incorrect datatype for value %s (expected %s, was %s)',
                    Report::value($json->toString()),
                    Report::value($needs),
                    Report::value($has)
                ));
            }
        }

        // If this structure is an object...

        if ($this->getOptions()->isExpectedType(Json::OBJECT) && $json->isType(Json::OBJECT)) {

            // Validate each of its children.

            $validKeys = [];
            $branchCount = 0;

            foreach ($this->getChildren() as $child) {

                try {

                    // If the child branches, do that and skip the rest.

                    if ($child->getOptions()->branches()) {

                        $branch = $child->getBranch();

                        // If the predicates succeed, validate the structure.

                        if ($branch->test($json)) {

                            // Cycle through all the structures in the branch.

                            $reports->addReport(Report::info('Successfully branched to %s', Report::key($branch->getLabel())));
                            $branchCount++;
                            $validKeys = array_merge($validKeys, $branch->compare($json, $reports));
                        }
                        
                        continue;
                    }

                    // Compare with the field, if existent.

                    if ($child->getKey() !== null) {

                        $child->compare($json->getField($child->getKey()), $reports->createChildReport($json->getField($child->getKey()), $child->getKey()));
                    }

                    // If the child is a placeholder, do special check.

                    if ($child->getOptions()->isPlaceholder() && !$child->getOptions()->usesAncestor()) {

                        // Create a clone in order to preserve this structure.

                        $placeholder = clone $child;
                        $placeholder->setOptions(clone $placeholder->getOptions());
                        $placeholder->getOptions()->setPlaceholder(false);
                        
                        // Cycle through all of Json's fields and use their keys instead.

                        $fields = $json->getFields($placeholder->getOptions()->getExpectedType(), $placeholder->getOptions()->isExpectedType(Json::NULL));

                        for ($i = 0, $j = count($fields->getCollection()); $i < $j; $i++) {

                            $currentJson = $fields->getCollection()[$i];

                            // Set up clone to do its work correctly.

                            $placeholder->setKey($currentJson->getKey());
                            $placeholder->compare($currentJson, $reports->createChildReport($currentJson, $currentJson->getKey()));

                            // Add key to list of valid keys.

                            $validKeys[] = $currentJson->getKey();
                        }
                    }
                } catch (Exception\NotFound $e) {

                    // Catch if field didn't exist.

                    if ($child->getOptions()->isRequired()) {

                        $reports->addReport(Report::fatal('Missing required field %s', Report::key($child->getKey())));
                    }
                } catch (Exception\JsonException $e) {

                    $reports->addReport(Report::fatal($e->getMessage())); // TODO: rebuild exceptions to allow args.
                }
            }

            // Check to ensure it branched the required number of times.

            $requiredBranchCount = $this->getRequiredBranches();

            if (($requiredBranchCount[0] !== null && $branchCount < $requiredBranchCount[0]) || ($requiredBranchCount[1] !== null && $branchCount > $requiredBranchCount[1])) {

                $reports->addReport(Report::warning('Structure branched %s time(s), must have branched %s', (string)$branchCount,
                (($requiredBranchCount[1] === null) ? 'at least ' . $requiredBranchCount[0] . ' time(s)' : 'between ' . $requiredBranchCount[0] . ' and ' . $requiredBranchCount[1] . ' times.'))
                );
            }

            // And check for invalid keys.

            $validKeys = array_merge($validKeys, $this->getValidKeys());
            $invalidKeys = $json->getInvalidKeys(...$validKeys);

            if (count($invalidKeys) > 0) {

                $reports->addReport(Report::warning('Found unexpected key(s): %s<br><br>Should instead be one of the following keys: %s', Report::key(...$invalidKeys), Report::key(...$validKeys)));
            }
        }

        // If the structure is an array...

        if ($this->getOptions()->isExpectedType(Json::ARRAY) && $json->isType(Json::ARRAY)) {

            $allFoundElements = [];

            // Cycle through each of the provided elements.

            $arrValues = $json->getElements(Json::ANY, true);
            $failures = [];

            for ($i = 0, $j = count($arrValues->getCollection()); $i < $j; $i++) {

                $value = $arrValues->getElement($i);
                $failed = true;

                // Then cycle through each of the expected elements.

                foreach ($this->getElements() as $element) {

                    // If the element itself is an ancestor, get that ancestor first.

                    if ($element->getOptions()->usesAncestor()) {

                        $element = clone $element->findAncestor($element->getOptions()->getAncestor());
                    }

                    // If the element is of the correct type, it passes the test and can be compared.

                    if ($value->isType($element->getOptions()->getExpectedType())) {
                        
                        $failed = false;
                        
                        $element->compare($value, $reports->createChildReport($value));
                    }
                }

                // If the provided element does not match any expected element, it failed.

                if ($failed) {

                    $failures[] = $value;
                }
                
            }

            // Cycle through all the failures and add a warning.

            for ($i = 0, $j = count($failures); $i < $j; $i++) {

                $reports->addReport(Report::warning('Element with the following value was not accepted: %s', Report::value($failures[$i]->toJsonString())));
            }
        }
    }

    /**
     * Returns a structure that has the supplied UUID.
     * 
     * Any structures given a UUID will be available to choose from.
     *
     * @param UuidInterface $uuid The UUID of the structure to retrieve.
     * @return self
     */
    public static function getStructureFromUuid(UuidInterface $uuid): self
    {
        if (!array_key_exists($uuid->toString(), self::$uuids)) {

            throw new Exception\BadStructure('There was no structure stored with the UUID "' . $uuid->toString() . '"');
        }

        return self::$uuids[$uuid->toString()];
    }

    /**
     * Finds a parent with the UUID of the specified ancestor.
     * 
     * If none are found, throws error instead.
     * 
     * If the parent hosts a branch, skip that parent and go to its parent instead,
     * as branching structure hosts are essentially just a middle-man.
     *
     * @param UuidInterface $ancestor The UUID of the ancestor to locate.
     * @return self
     */
    public function findAncestor(UuidInterface $ancestor): self
    {
        // If there's no parent, we've gone as far as we could and failed to find the ancestor.

        if ($this->getParent() === null) {

            throw new Exception\BadStructure('Could not locate ancestor "' . $ancestor . '"');
        }

        // If the parent branches, or doesn't have the UUID, or the UUID doesn't match, continue on to its parent.

        if ($this->getParent()->getOptions()->branches() || $this->getParent()->getUuid() === null || !$ancestor->equals($this->getParent()->getUuid())) {

            return $this->getParent()->findAncestor($ancestor);
        }

        // Otherwise it matched, return the parent.

        return $this->getParent();
    }

    /**
     * Sets the UUID of the structure, used with redirecting and ascending.
     *
     * @param UuidInterface $uuid The UUID of the structure.
     * @return void
     */
    public function setUuid(UuidInterface $uuid = null): self
    {
        $this->uuid = $uuid;

        if ($uuid === null) {

            // If the supplied UUID was null, that indicates "remove the UUID".

            if (array_key_exists($uuid->toString(), self::$uuids)) {

                unset(self::$uuids[$uuid->toString()]);
            }
        } else {

            // If the supplied UUID was not null, that indicates "save the UUID".

            self::$uuids[$uuid->toString()] = $this;
        }

        // Return the structure.

        return $this;
    }

    /**
     * Returns the UUID of the structure, if it has one.
     *
     * @return UuidInterface|null
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * Returns the valid keys of the structure.
     *
     * @return array
     */
    public function getValidKeys(): array
    {
        $keys = [];

        foreach ($this->getChildren() as $child) {

            if ($child->getKey() !== null) {

                $keys[] = $child->getKey();
            }
        }

        return $keys;
    }

    /**
     * Returns a designated root structure. May specify which datatypes it can be.
     *
     * @param integer $type The datatypes of the root.
     * @param self ...$children The child/element structures of the root, if it's an object or array.
     * @return self
     */
    public static function root(int $type = Json::OBJECT, self ...$children): self
    {
        $structure = new static(null, OptionsBuilder::required()::type($type)::build());

        if ($structure->getOptions()->isExpectedType(Json::OBJECT)) {

            $structure->addChildren(...$children);
        }

        if ($structure->getOptions()->isExpectedType(Json::ARRAY)) {

            $structure->addElements(...$children);
        }

        return $structure;
    }

    /**
     * Specifies a boolean structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function boolean(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::BOOLEAN)::required($required)::build());
    }

    /**
     * Specifies an integer structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function integer(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::INTEGER)::required($required)::build());
    }

    /**
     * Specifies a double structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function double(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::DOUBLE)::required($required)::build());
    }

    /**
     * Specifies a numeric structure.
     * 
     * This includes: integer, double.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function number(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::NUMBER)::required($required)::build());
    }

    /**
     * Specifies a string structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function string(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::STRING)::required($required)::build());
    }

    /**
     * Specifies a scalar structure.
     * 
     * This includes: boolean, integer, double, string.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function scalar(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::SCALAR)::required($required)::build());
    }

    /**
     * Specifies a null structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function null(string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::type(Json::NULL)::required($required)::build());
    }

    /**
     * Specifies a structure with a variety of datatypes, as supplied.
     *
     * @param string $key The key of the field.
     * @param integer $type The datatype of the structure.
     * @param boolean $required Whether or not the structure is required.
     * @param self ...$children Any children/elements, provided this mixed structure can be an object or array.
     * @return self
     */
    public static function mixed(string $key = null, int $type = Json::ANY, bool $required = true, self ...$children): self
    {
        $structure = new static($key, OptionsBuilder::required($required)::type($type)::build());

        if ($structure->getOptions()->isExpectedType(Json::OBJECT)) {

            $structure->addChildren(...$children);
        }

        if ($structure->getOptions()->isExpectedType(Json::ARRAY)) {

            $structure->addElements(...$children);
        }

        return $structure;
    }

    /**
     * Specifies an object structure with optional children.
     *
     * @param string $key The key of the object.
     * @param boolean $required Whether or not the structure is required.
     * @param self ...$children The children within the object.
     * @return self
     */
    public static function object(string $key = null, bool $required = true, self ...$children): self
    {
        return new static($key, OptionsBuilder::type(Json::OBJECT)::required($required)::build(), ...$children);
    }

    /**
     * Specifies an array structure.
     *
     * @param string $key The key of the field.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function array(string $key = null, bool $required = true, self ...$elements): self
    {
        $structure = new static($key, OptionsBuilder::type(Json::ARRAY)::required($required)::build());

        $structure->addElements(...$elements);

        return $structure;
    }

    /**
     * Specifies a structure in which the key name in Json can be anything,
     * provided that the Json matches the specified type of the placeholder.
     * Any Json fields that do not match the datatype will not throw an error
     * immediately, but rather will be validated against any other structures
     * that are a sibling to the placeholder.
     *
     * @param integer $type The datatype of the placeholder.
     * @param self ...$children Optional children, if the structure is an object.
     * @return self
     */
    public static function placeholder(int $type, self ...$children): self
    {
        $structure = new static(null, OptionsBuilder::placeholder()::type($type)::build());

        if ($structure->getOptions()->isExpectedType(Json::OBJECT)) {

            $structure->addChildren(...$children);
        }

        if ($structure->getOptions()->isExpectedType(Json::ARRAY)) {

            $structure->addElements(...$children);
        }

        return $structure;
    }

    /**
     * Ascends through the ancestors of the structure to find an ancestor
     * that has the provided $ancestor key. Typically if the $ancestor is null, the
     * resulting ancestor will be the root of the structure, allowing for
     * a totally recursive structure.
     * 
     * $key will be the name of the field to copy the ancestor to. It can also be null,
     * such as when used is arrays.
     *
     * @param string $key The key of the structure that will replicate the structure of the ancestor.
     * @param boolean $required Whether or not the structure is required.
     * @param string $ancestor The key of the ancestor to locate, used as the value of $key.
     * @return self
     */
    public static function ascend(UuidInterface $ancestor, string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::ancestor($ancestor)::required($required)::build());
    }

    /**
     * Takes in the UUID of any other structure and redirects to it.
     * 
     * All structures given a UUID will be stored and accessible, even
     * if they aren't part of the same structure. Make sure your UUIDs
     * are all, of course, unique!
     *
     * @param UuidInterface $target The UUID of the target structure to redirect to.
     * @param string $key The key of the structure that will replicate the structure of the redirect.
     * @param boolean $required Whether or not the structure is required.
     * @return self
     */
    public static function redirect(UuidInterface $target, string $key = null, bool $required = true): self
    {
        return new static($key, OptionsBuilder::redirect($target)::required($required)::build());
    }

    /**
     * Creates a branching structure using the provided data.
     *
     * @param string $label The user-friendly label of the branch.
     * @param Structure $branch The structure that will be traversed if the predicates succeed.
     * @param IPredicate ...$predicates The predicates that all must succeed to traverse the branch.
     * @return self
     */
    public static function branch(string $label, IPredicate $predicate, Structure ...$branches): self
    {
        $structure = new static(null, OptionsBuilder::required()::branches()::build());
        $structure->setBranch(new Branch($label, $predicate, ...$branches));

        return $structure;
    }
}