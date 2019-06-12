<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Json;

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
     * @param Json $json
     */
    public function __construct(Json $json, string $key = null)
    {
        $this->key = $key;
        $this->json = $json;
    }

    /**
     * Returns the expected key to be found in JSON.
     *
     * @return string|null
     */
    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Returns the JSON stored in the report.
     *
     * @return Json
     */
    public function getJson(): Json
    {
        return $this->json;
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
     * Returns whether or not there are any non-fatal warnings.
     *
     * @return boolean
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
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
     * Cycles through all children and their children to determine
     * whether there is any info at any depth.
     *
     * @return boolean
     */
    public function hasAnyInfo(): bool
    {
        if ($this->hasInfo()) {

            return true;
        }

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            if ($this->children[$i]->hasAnyInfo()) {

                return true;
            }
        }

        return false;
    }

    /**
     * Cycles through all children and their children to determine
     * whether there are any errors at any depth.
     *
     * @return boolean
     */
    public function hasAnyWarnings(): bool
    {
        if ($this->hasWarnings()) {

            return true;
        }

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            if ($this->children[$i]->hasAnyWarnings()) {

                return true;
            }
        }

        return false;
    }

    /**
     * Cycles through all children and their children to determine
     * whether there are any fatals at any depth.
     *
     * @return boolean
     */
    public function hasAnyFatals(): bool
    {
        if ($this->isFatal()) {

            return true;
        }

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            if ($this->children[$i]->hasAnyFatals()) {

                return true;
            }
        }

        return false;

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
     * Returns an array containing strings of all the informational messages.
     *
     * @return array
     */
    public function getAllInfo(): array
    {
        $info = $this->info;

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            $info = array_merge($info, $this->children[$i]->getAllInfo());
        }

        return $info;
    }

    /**
     * Returns an array containing strings of all the warning messages.
     *
     * @return array
     */
    public function getAllWarnings(): array
    {
        $warnings = $this->warnings;

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            $warnings = array_merge($warnings, $this->children[$i]->getAllWarnings());
        }

        return $warnings;
    }

    /**
     * Returns an array containing strings of all the fatal error messages.
     *
     * @return array
     */
    public function getAllFatals(): array
    {
        $fatals = $this->fatals;

        for ($i = 0, $j = count($this->children); $i < $j; $i++) {

            $fatals = array_merge($fatals, $this->children[$i]->getAllFatals());
        }

        return $fatals;
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