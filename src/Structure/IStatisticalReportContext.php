<?php namespace Celestriode\JsonUtils\Structure;

interface IStatisticalReportContext extends IReportContext
{
    /**
     * Adds data to the statistics as determined by the context.
     *
     * @param Statistics $statistics The statistics to add to.
     * @return void
     */
    public function addContextToStatistics(Statistics $statistics): void;
}