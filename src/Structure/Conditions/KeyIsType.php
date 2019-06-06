<?php namespace Celestriode\JsonUtils\Structure\Conditions;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\JsonUtils;

class KeyIsType implements ICondition
{
    protected $type;
    protected $key;

    public function __construct(int $type, string $key = null)
    {
        $this->type = $type;
        $this->key = $key;
    }

    /**
     * Checks if the specified key's value is of the expected type.
     * 
     * When no key was specified, it will use the structure's key instead.
     *
     * @param \stdClass $json The JSON at the current depth.
     * @param Structure $structure The expected structure.
     * @param Reports $reports Error reporting collection.
     * @param boolean $announce Whether or not to add errors to reports.
     * @return void
     */
    public function validate(\stdClass $json, Structure $structure, Reports $reports, bool $announce = true): bool
    {
        $key = $this->key ?? $structure->getKey();

        // Skip if the key doesn't exist.

        if (!isset($json->{$key})) {

            return false;
        }

        $element = $json->{$key};

        $correct = ($this->type & JsonUtils::normalizeTypeString(gettype($element))) !== 0;

        if ($announce && !$correct) {

            $reports->addFatal('Key "' . $key . '" does not match the expected type ""');
        }

        return $correct;
    }
}