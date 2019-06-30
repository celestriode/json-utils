<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Exception\WrongType;

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
     * @param Json $json The relevant key to this report.
     * @param string $key The relevant Json to this report.
     */
    public function __construct(Json $json = null, string $key = null)
    {
        $this->setKey($key);
        $this->setJson($json);
    }

    /**
     * Stores the key referring to the incoming Json.
     *
     * @param string $key The key referring to the Json.
     * @return void
     */
    public function setKey(string $key = null): void
    {
        $this->key = $key;
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
     * Sets the Json object that this report is referring directly to.
     *
     * @param Json $json The Json at the current depth.
     * @return void
     */
    public function setJson(Json $json = null): void
    {
        $this->json = $json;
    }

    /**
     * Returns the JSON stored in the report.
     *
     * @return Json|null
     */
    public function getJson(): ?Json
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
     * Adds a report to the various list of reports.
     *
     * @param Report $report The report to add.
     * @return void
     */
    public function addReport(Report $report): void
    {
        switch ($report->getType()) {

            case Report::TYPE_INFO:
                $this->info[] = $report;
                break;
            case Report::TYPE_WARNING:
                $this->warnings[] = $report;
                break;
            case Report::TYPE_FATAL:
                $this->fatals[] = $report;
                break;
            default:
                throw new WrongType('Invalid report type "' . $report->getType() . '"');
        }

        // Set the Json of the stored report.

        $report->setJson($this->getJson());
    }

    /**
     * Returns whether or not there is extra information in the report.
     *
     * @return boolean
     */
    public function hasInfo(): bool
    {
        return !empty($this->getInfo());
    }

    /**
     * Returns whether or not there are any non-fatal warnings.
     *
     * @return boolean
     */
    public function hasWarnings(): bool
    {
        return !empty($this->getWarnings());
    }

    /**
     * Returns whether or not there are errors (both fatal and non-fatal).
     *
     * @return boolean
     */
    public function hasErrors(): bool
    {
        return !empty($this->getWarnings()) || !empty($this->getFatals());
    }

    /**
     * Returns whether or not there are fatal errors.
     *
     * @return boolean
     */
    public function isFatal(): bool
    {
        return !empty($this->getFatals());
    }

    /**
     * Returns whether or not there are any messages of any kind.
     *
     * @return boolean
     */
    public function hasMessages(): bool
    {
        return !empty($this->getInfo()) || $this->hasErrors();
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

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            if ($this->getChildReports()[$i]->hasAnyInfo()) {

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

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            if ($this->getChildReports()[$i]->hasAnyWarnings()) {

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

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            if ($this->getChildReports()[$i]->hasAnyFatals()) {

                return true;
            }
        }

        return false;

    }

    /**
     * Returns whether or not this report or any of its children has any
     * warnings or fatals.
     *
     * @return boolean
     */
    public function hasAnyErrors(): bool
    {
        return $this->hasErrors() || $this->hasAnyWarnings() || $this->hasAnyFatals();
    }

    /**
     * Returns whether or not this report or any of its children has any
     * messages of any kind.
     *
     * @return boolean
     */
    public function hasAnyMessages(): bool
    {
        return $this->hasMessages() || $this->hasAnyInfo() || $this->hasAnyErrors();
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
     * Returns messages that are either errors or fatals.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return array_merge($this->getWarnings(), $this->getFatals());
    }

    /**
     * Returns all messages of any kind.
     *
     * @return array
     */
    public function getMessages(): array
    {
        return array_merge($this->getInfo(), $this->getErrors());
    }

    /**
     * Returns an array containing strings of all the informational messages.
     *
     * @return array
     */
    public function getAllInfo(): array
    {
        $info = $this->getInfo();

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            $info = array_merge($info, $this->getChildReports()[$i]->getAllInfo());
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
        $warnings = $this->getWarnings();

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            $warnings = array_merge($warnings, $this->getChildReports()[$i]->getAllWarnings());
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
        $fatals = $this->getFatals();

        for ($i = 0, $j = count($this->getChildReports()); $i < $j; $i++) {

            $fatals = array_merge($fatals, $this->getChildReports()[$i]->getAllFatals());
        }

        return $fatals;
    }

    /**
     * Returns an array containing strings of warning and fatal error messages.
     *
     * @return array
     */
    public function getAllErrors(): array
    {
        return array_merge($this->getAllWarnings(), $this->getAllFatals());
    }

    /**
     * Returns an array containing strings of all messages of any kind.
     *
     * @return array
     */
    public function getAllMessages(): array
    {
        return array_merge($this->getAllInfo(), $this->getAllErrors());
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

    /**
     * Creates a new child report and automatically sets it as the child
     * of this report. Ensures that the new child is of whatever the
     * actual reports class is, such as if it's been extended.
     *
     * @param Json $json The relevant key to this report.
     * @param string $key The relevant Json to this report.
     * @return self
     */
    public function createChildReport(Json $json, string $key = null): self
    {
        $child = new static($json, $key);

        $this->addChildReport($child);

        return $child;
    }
}