<?php namespace Celestriode\JsonUtils\Structure;

interface IReportContext
{
    /**
     * Convert context at the current depth to a string.
     *
     * @param boolean $prettify Make it look nicer, if applicable.
     * @return string
     */
    public function toString(bool $prettify = false): string;
}