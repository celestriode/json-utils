<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Structure\Reports;

class StatisticalReports extends Reports
{
    protected $statistics;

    /**
     * Stores the expected key and context at the current depth.
     * 
     * Info is for non-breaking issues.
     * 
     * Warnings are typically for values.
     * 
     * Fatals are typically for keys.
     *
     * @param IReportContext $context The relevant context to this report.
     * @param string $key The relevant key to this report.
     * @param Statistics $stats The statistics to populate when creating the reports tree.
     */
    public function __construct(IReportContext $context = null, string $key = null, Statistics $stats = null)
    {
        $this->setStatistics($stats ?? new Statistics());

        parent::__construct($context, $key);
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
     * Hijacks the setContext method to populate statistics using the incoming context.
     *
     * @param IReportContext $context The context to populate statistics with.
     * @return void
     */
    public function setContext(IReportContext $context = null): void
    {
        parent::setContext($context);

        if ($context !== null && $context instanceof IStatisticalReportContext) {

            $context->addContextToStatistics($this->getStatistics());
        }
    }

    /**
     * Hijacks the createChildReport in order to pass the root Statistics object
     * down through all future children.
     *
     * @param IReportContext $context The relevant context to this report.
     * @param string $key The relevant key to this report.
     * @return Reports
     */
    public function createChildReport(IReportContext $context, ?string $key = null): Reports
    {
        $child = new static($context, $key, $this->getStatistics());

        $this->addChildReport($child);

        return $child;
    }
}