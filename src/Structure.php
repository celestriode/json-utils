<?php namespace Celestriode\JsonUtils;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure\Options;
use function DeepCopy\deep_copy;

class Structure
{
    protected $key;
    protected $options;
    protected $children = [];
    protected $validKeys = [];

    private $originalKey;
    private $originalOptions = [];
    private $originalChildren = [];

    /**
     * Stores the expected structure along with various options
     * as well as child structures (provided that this structure
     * is an object or array)
     *
     * @param string $key The name of the key.
     * @param Options $options The various options of this structure.
     * @param self ...$children The children of the structure.
     */
    public function __construct(string $key, Options $options = null, self ...$children)
    {
        $this->key = $this->originalKey = $key;
        $this->options = $options ?? new Options();
        $this->originalOptions = deep_copy($this->options);
        $this->addChildren(...$children);
        $this->originalChildren = $children;
    }

    /**
     * Initiates comparison between the preset structure and the incoming jSON object.
     * 
     * Issues are added to $reports, which is returned after completion.
     *
     * @param \stdClass $json The JSON to compare with.
     * @param Structure\Reports $reports Reports to add to.
     * @param self $parent The parent of the structure, if applicable.
     * @return Structure\Reports
     */
    public function compare(\stdClass $json, Structure\Reports $reports = null, self $parent = null): Structure\Reports
    {
        $reports = $reports ?? new Structure\Reports($this->key, $json);

        $this->validate($json, $reports, $parent);

        return $reports;
    }

    /**
     * Compares the expected structure to the incoming JSON object.
     * 
     * Adds issues with the structure to $reports and returns said reports.
     *
     * @param \stdClass $json The JSON to compare with.
     * @param Structure\Reports $reports Reports to add to.
     * @param self $parent The parent of the structure, if applicable.
     * @return void
     */
    protected function validate(\stdClass $json, Structure\Reports $reports, self $parent = null)
    {
        // Check the optional conditions of this structure. If it succeeds.. do nothing? TODO: maybe do something.

        if (!$this->options->checkConditions($json, $this, $reports)) {

            //$reports->addWarning('A condition has failed.');
        }

        // If the structure is supposed to be empty, skip it.

        if ($this->options->isEmpty()) {

            return;
        }

        // If the structure is a placeholder, populate the parent and skip the rest of this.

        if ($this->options->isPlaceholder()) {

            // Get all the keys at the placeholder's depth.

            $keys = JsonUtils::getKeys($json);

            // Add all children from JSON in place of the placeholder. TODO: ignore if parent has a child with that key already to allow semi-dynamic content.
            // TODO: allow the root to be lenient.

            for ($i = 0, $j = count($keys); $i < $j; $i++) {

                $newChild = clone $this;
                $newChild->setKey($keys[$i]);
                $newChild->getOptions()->setIsPlaceholder(false);

                if ($parent !== null) {

                    $parent->addChild($newChild);
                }
            }

            // Don't validate a placeholder.

            return;
        } else if ($parent !== null) {

            // Validate datatype.

            try {

                $this->getOptions()->validateType($json, $this->getKey(), $reports);
            } catch (Exception\JsonException $e) {

                $reports->addFatal($e->getMessage());
            }

        }

        // If this is a list, do list validation and don't continue.

        if ($this->options->isType(JsonUtils::ARRAY)) {

            try {

                /// Get the array silently.

                $array = JsonUtils::getArray($this->getKey(), $json);
            } catch (Exception\JsonException $e) {

                return;
            }
            
            // Cycle through each element in the incoming array.
            
            for ($i = 0, $j = count($array); $i < $j; $i++) {

                // Get the current element.

                $element = $array[$i];
                $buffer = new \stdClass();
                $buffer->{$this->getKey()} = $element;

                if (!$this->options->validateBranches($buffer, $this, $reports)) {

                    $reports->addWarning('Invalid list element "' . JsonUtils::toString($element) . '"');
                }
            }

            return;
        }

        // Add any valid branches as children to this structure.

        $this->options->validateBranches($json, $this, $reports);

        // Check the children of this structure.

        $i = 0; // Maximum number of children. TODO: lift limit?

        while ($i < 256) { // TODO: refactor because this is just awful.

            // If there are no more children available, all done.

            if (!isset($this->children[$i])) {

                break;
            }

            $child = $this->children[$i];

            // If the child is a placeholder, do its partial validation to populate children and continue.

            if ($child->getOptions()->isPlaceholder() || $child->getOptions()->isEmpty()) {

                $child->compare($json, $child->getOptions()->isPlaceholder() ? null : $reports, $this);

                $i++;
                continue;
            }

            // If the child was required but didn't exist, error and don't evaluate what doesn't exist.

            if (!JsonUtils::hasKey($child->getKey(), $json)) {

                if ($child->getOptions()->isRequired()) {

                    $reports->addFatal('Missing required key "' . $child->getKey() . '"');
                }

                $i++;
                continue;
            }

            // If the expected datatype is an object, attempt to get that object.

            if ($child->getOptions()->getType() === JsonUtils::OBJECT || ($child->getOptions()->getType() === JsonUtils::ANY && JsonUtils::hasObject($child->getKey(), $json))) {

                try {
                    $child->compare(JsonUtils::getObject($child->getKey(), $json), $reports->addChildReport(new Structure\Reports($child->getKey(), JsonUtils::getObject($child->getKey(), $json))), $this);
                } catch (Exception\JsonException $e) {

                    $reports->addFatal($e->getMessage());
                }
            } else {

                // Otherwise validate the non-object.

                $child->compare($json, $reports->addChildReport(new Structure\Reports($child->getKey(), $json)), $this);
            }

            $i++;
        }

        // If this is an object, check for invalid keys.

        if ($this->options->getType() === JsonUtils::OBJECT) {

            $invalidKeys = JsonUtils::getInvalidKeys($json, ...$this->validKeys);

            for ($i = 0, $j = count($invalidKeys); $i < $j; $i++) {

                $reports->addWarning('Unexpected key "' . $invalidKeys[$i] . '"; check for typos');
            }
        }

        // Reset unplanned data.

        $this->removeUnplannedData();
    }

    /**
     * Resets data to their original form after validation.
     * 
     * TODO: not need to do this, this is just lazy programming.
     *
     * @return void
     */
    private function removeUnplannedData(): void
    {
        $this->children = [];
        $this->validKeys = [];
        $this->setKey($this->originalKey);
        $this->options = deep_copy($this->originalOptions);
        $this->addChildren(...$this->originalChildren);
    }

    /**
     * Adds multiple children to the structure.
     *
     * @param self ...$children The children to add.
     * @return void
     */
    public function addChildren(self ...$children): void
    {
        for ($i = 0, $j = count($children); $i < $j; $i++) {

            $this->addChild($children[$i]);
        }
    }

    /**
     * Adds a single child to the structure.
     *
     * @param self $child The child to add.
     * @return void
     */
    public function addChild(self $child): void
    {
        // Do not add child if it already exists.

        if (in_array($child->getKey(), $this->validKeys)) {

            return;
        }

        // Otherwise add the child.

        $this->children[] = deep_copy($child);

        // If the child isn't a placeholder, add its key to the list of valid keys.

        if (!$child->getOptions()->isPlaceholder()) {

            $this->addValidKey($child->getKey());
        }
    }

    /**
     * Adds a single condition to the structure.
     * 
     * The condition will not prohibit children from being validated. Instead,
     * the condition is used for additional custom error reporting.
     * 
     * If you need to prohibit children from being validated based on a condition,
     * use a branch instead of children.
     *
     * @param Conditions\ICondition $condition The condition that will be checked.
     * @return self
     */
    public function addCondition(Structure\Conditions\ICondition $condition): self
    {
        $this->options->addCondition($condition);

        return $this;
    }

    /**
     * Adds a branch that will only be validated provided that the condition
     * succeeds.
     *
     * @param string $branchName The name of the branch, used for error reporting.
     * @param self $structure The structure within this branch.
     * @param Structure\Conditions\ICondition ...$conditions The conditions that must succeed to validate this branch.
     * @return self
     */
    public function addBranch(string $branchName, self $structure, Structure\Conditions\ICondition ...$conditions): self
    {
        $this->options->addBranch(new Structure\Branch($branchName, $this, $structure, ...$conditions));

        return $this;
    }

    /**
     * Adds multiple keys to the list of valid keys within
     * this structure.
     *
     * @param string ...$keys The keys to add.
     * @return void
     */
    public function addValidKeys(string ...$keys): void
    {
        for ($i = 0, $j = count($keys); $i < $j; $i++) {

            $this->addValidKey($keys[$i]);
        }
    }

    /**
     * Adds a key that can be contained within this structure.
     * This is used for error-reporting purposes. If there are
     * keys in the JSON input that are not within the list of
     * valid keys, they will be reported on.
     *
     * @param string $key The key to add.
     * @return void
     */
    public function addValidKey(string $key): void
    {
        $this->validKeys[] = $key;
    }

    /**
     * Sets the expected key name of this structure.
     *
     * @param string $key The key to set.
     * @return void
     */
    final public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Returns all valid keys that can be contained within this structure.
     *
     * @return array
     */
    final public function getValidKeys(): array
    {
        return $this->validKeys;
    }

    /**
     * Returns all children of this structure.
     *
     * @return array
     */
    final public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Returns the structure's options.
     *
     * @return Options
     */
    final public function getOptions(): Options
    {
        return $this->options;
    }

    /**
     * Returns the key of the structure.
     *
     * @return string
     */
    final public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Creates a structure intended to be the root of the JSON structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @param self ...$children The structures nested within this structure.
     * @return self
     */
    public static function root(self ...$children): self
    {
        return new Structure('ROOT', new Options(JsonUtils::OBJECT), ...$children);
    }

    /**
     * Creates a structure that can match any key but must match the expected type.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @param self ...$children The structures nested within this structure.
     * @return self
     */
    public static function placeholder(int $type = JsonUtils::ANY, bool $required = true, self ...$children): self
    {
        return new Structure('PLACEHOLDER', new Options($type, $required, true), ...$children);
    }

    /**
     * Creates a structure that is can contain nothing at all.
     *
     * @return self
     */
    public static function empty(): self
    {
        $options = new Options();
        $options->setEmpty(true);

        return new Structure('EMPTY', $options);
    }

    /**
     * Creates a typeless structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function any(string $key, bool $required = true): self
    {
        return new Structure($key);
    }

    /**
     * Creates a boolean structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function boolean(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::BOOLEAN, $required));
    }

    /**
     * Creates an integer structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function integer(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::INTEGER, $required));
    }

    /**
     * Creates a double structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function double(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::DOUBLE, $required));
    }

    /**
     * Creates a number (integer + double) structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function number(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::NUMBER, $required));
    }
    
    /**
     * Creates a string structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function string(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::STRING, $required));
    }

    /**
     * Creates an array structure.
     * 
     * Use branches to validate each element.
     * 
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @return self
     */
    public static function array(string $key, bool $required = true): self
    {
        return new Structure($key, new Options(JsonUtils::ARRAY, $required));
    }

    /**
     * Creates an object structure.
     *
     * @param string $key The key of the structure.
     * @param boolean $required Whether or not this structure is required.
     * @param self ...$children The structures nested within this structure.
     * @return self
     */
    public static function object(string $key, bool $required = true, self ...$children): self
    {
        return new Structure($key, new Options(JsonUtils::OBJECT, $required), ...$children);
    }
}