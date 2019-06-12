<?php namespace Celestriode\JsonUtils\Structure\Audits;

use Celestriode\JsonUtils\IAudit;
use Celestriode\JsonUtils\TMultiSingleton;

abstract class Audit implements IAudit
{
    use TMultiSingleton;
}