<?php namespace Celestriode\JsonUtils\Structure;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

class Reports
{
    private $key;
    private $json;

    private $info = [];
    private $warnings = [];
    private $fatals = [];

    private $children = [];

    /**
     * Stores the expected key and JSON at the current depth.
     * 
     * Info is for non-breaking issues.
     * 
     * Warnings are typically for values.
     * 
     * Fatals are typically for keys.
     *
     * @param string $key
     * @param \stdClass $json
     */
    public function __construct(string $key, \stdClass $json)
    {
        $this->key = $key;
        $this->json = $json;
    }

    /**
     * Adds a child report to this report.
     * 
     * This is used for depth traversal.
     * 
     * Returns the child.
     *
     * @param self $child The child to add.
     * @return void
     */
    public function addChildReport(self $child): self
    {
        $this->children[] = $child;

        return $child;
    }

    /**
     * Adds non-error information to the report.
     *
     * @param string $info
     * @return void
     */
    public function addInfo(string $info): void
    {
        $this->info[] = $info;
    }

    /**
     * Adds a non-fatal warning to the report.
     *
     * @param string $warning
     * @return void
     */
    public function addWarning(string $warning): void
    {
        $this->warnings[] = $warning;
    }

    /**
     * Adds a fatal warning to the report.
     *
     * @param string $fatal
     * @return void
     */
    public function addFatal(string $fatal): void
    {
        $this->fatals[] = $fatal;
    }

    /**
     * Returns whether or not there is extra information in the report.
     *
     * @return boolean
     */
    public function hasInfo(): bool
    {
        return !empty($this->info);
    }

    /**
     * Returns whether or not there are errors (both fatal and non-fatal).
     *
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return !empty($this->warnings) && !empty($this->fatals);
    }

    /**
     * Returns whether or not there are fatal errors.
     *
     * @return boolean
     */
    public function isFatal(): bool
    {
        return !empty($this->fatals);
    }

    /**
     * Returns all information.
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Returns all non-fatal warnings.
     *
     * @return array
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns all fatal warnings.
     *
     * @return array
     */
    public function getFatals(): array
    {
        return $this->fatals;
    }

    /**
     * Returns all child reports.
     *
     * @return array
     */
    public function getChildReports(): array
    {
        return $this->children;
    }
}