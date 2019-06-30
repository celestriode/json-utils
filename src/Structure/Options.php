<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Json;
use Ramsey\Uuid\UuidInterface;

class Options
{
    protected $expectedType = Json::ANY;
    protected $required = true;
    protected $placeholder = false;
    protected $branches = false;
    protected $usesAncestor = false;
    protected $skip = false;
    protected $ancestor;

    /**
     * Sets the expected datatype when matching against Json.
     *
     * @param integer $expectedType The expected type.
     * @return void
     */
    public function setExpectedType(int $expectedType): void
    {
        $this->expectedType = $expectedType;
    }

    /**
     * Sets whether or not this structure must exist within the input.
     *
     * @param boolean $required
     * @return void
     */
    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    /**
     * Sets whether or not the structure's key can match any key in the input.
     *
     * @param boolean $placeholder True if the input's key can be anything.
     * @return void
     */
    public function setPlaceholder(bool $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    /**
     * Whether or not this structure's key can match any key in the input.
     *
     * @return boolean
     */
    public function isPlaceholder(): bool
    {
        return $this->placeholder;
    }

    /**
     * Marks the structure as being a branch.
     *
     * @param boolean $branches True if it branches.
     * @return void
     */
    public function setBranches(bool $branches): void
    {
        $this->branches = $branches;
    }

    /**
     * Returns whether or not this structure branches elsewhere based on predicates.
     *
     * @return boolean
     */
    public function branches(): bool
    {
        return $this->branches;
    }

    /**
     * Returns whether or not the structure must exist within the input.
     *
     * @return boolean
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Sets the ancestor UUID for the structure.
     *
     * @param UuidInterface $ancestor The UUID of the ancestor to locate.
     * @param boolean $uses Whether or not it actually uses an ancestor.
     * @return void
     */
    public function setAncestor(UuidInterface $ancestor = null, bool $uses = true): void
    {
        $this->ancestor = $ancestor;
        $this->usesAncestor = $uses;
    }

    /**
     * Returns whether or not this structure makes use of an ancestor.
     *
     * @return boolean
     */
    public function usesAncestor(): bool
    {
        return $this->usesAncestor;
    }

    /**
     * Returns the UUID of the ancestor, if existent.
     *
     * @return UuidInterface|null
     */
    public function getAncestor(): ?UuidInterface
    {
        return $this->ancestor;
    }

    /**
     * Returns the expected datatype that the Json should have.
     *
     * @return integer
     */
    public function getExpectedType(): int
    {
        return $this->expectedType;
    }

    /**
     * Returns whether or not the expected datatype of this structure
     * matches the specified datatype.
     *
     * @param integer $type The datatype to check if valid.
     * @return boolean
     */
    public function isExpectedType(int $type): bool
    {
        return ($this->getExpectedType() & $type) !== 0;
    }

    /**
     * Whether or not the structure should be skipped after performing
     * audits. This should primarily be set to true while within an
     * audit that should prevent the structure from being evaluated.
     *
     * @param boolean $skip True if the structure should be skipped after auditing.
     * @return void
     */
    public function setSkip(bool $skip): void
    {
        $this->skip = $skip;
    }

    /**
     * Returns whether or not the whole structure should be skipped
     * after auditing.
     * 
     * Audits in particular should be what sets the structure to skip.
     *
     * @return boolean
     */
    public function skip(): bool
    {
        return $this->skip;
    }
}