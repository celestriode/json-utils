<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Structure\Reports;
use Celestriode\JsonUtils\Json;

class StatisticalReports extends Reports
{
    protected $statistics;

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
     * @param Statistics $stats The statistics to populate when creating the reports tree.
     */
    public function __construct(Json $json = null, string $key = null, Statistics $stats = null)
    {
        $this->setStatistics($stats ?? new Statistics());

        parent::__construct($json, $key);
    }

    /**
     * Sets the statistics for use with the reports.
     *
     * @param Statistics $stats The statistics to populate.
     * @return void
     */
    public function setStatistics(Statistics $stats): void
    {
        $this->statistics = $stats;
    }

    /**
     * Returns the stored statistics paired with this report.
     *
     * @return Statistics
     */
    public function getStatistics(): Statistics
    {
        return $this->statistics;
    }

    /**
     * Hijacks the setJson method to populate statistics using the incoming Json.
     *
     * @param Json $json The Json to populate statistics with.
     * @return void
     */
    public function setJson(Json $json = null): void
    {
        parent::setJson($json);

        if ($json !== null) {

            $this->getStatistics()->addJsonToStats($json);
        }
    }

    /**
     * Hijacks the createChildReport in order to pass the root Statistics object
     * down through all future children.
     *
     * @param Json $json The relevant key to this report.
     * @param string $key The relevant Json to this report.
     * @return Reports
     */
    public function createChildReport(Json $json, ?string $key = null): Reports
    {
        $child = new static($json, $key, $this->getStatistics());

        $this->addChildReport($child);

        return $child;
    }
}