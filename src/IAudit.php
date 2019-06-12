<?php namespace Celestriode\JsonUtils;

use Celestriode\JsonUtils\Structure;
use Celestriode\JsonUtils\Json;
use Celestriode\JsonUtils\Structure\Reports;

interface IAudit
{
    /**
     * Custom verification of structures and Json with support for
     * error reporting.
     * 
     * These are similar to predicates in terms of testing, but can
     * be multi-focused and has access to the structure itself.
     * 
     * Audits can make use of multiple predicates, thus it's the
     * predicates that hold potential error messages. The audits
     * will instead determine the severity of the error.
     *
     * @param Structure $structure The structure at the current depth.
     * @param Json $json The Json at the current depth.
     * @param Reports $reports Reports at the current depth.
     * @return void
     */
    public function audit(Structure $structure, Json $json, Reports $reports): void;
}