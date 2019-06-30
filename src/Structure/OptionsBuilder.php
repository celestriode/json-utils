<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\Json;
use Ramsey\Uuid\UuidInterface;

class OptionsBuilder
{
    public static $type = Json::ANY;
    public static $required = true;
    public static $placeholder = false;
    public static $branches = false;
    public static $usesAncestor = false;
    public static $ancestor;

    /**
     * Marks the structure as being required.
     *
     * @param boolean $value True if required.
     * @return string
     */
    public static function required(bool $value = true): string
    {
        self::$required = $value;

        return __CLASS__;
    }

    /**
     * Marks the structure's key as being a placeholder.
     *
     * @param boolean $value True if a placeholder.
     * @return string
     */
    public static function placeholder(bool $value = true): string
    {
        self::$placeholder = $value;

        return __CLASS__;
    }

    /**
     * Specifies the expected datatype of the structure.
     *
     * @param integer $type The datatype.
     * @return string
     */
    public static function type(int $type): string
    {
        self::$type = $type;

        return __CLASS__;
    }

    /**
     * Marks the structure as branching.
     *
     * @param boolean $branches True if branching.
     * @return string
     */
    public static function branches(bool $branches = true): string
    {
        self::$branches = $branches;

        return __CLASS__;
    }

    /**
     * Marks the structure as a placeholder for an ancestor.
     *
     * @param UuidInterface $ancestor
     * @return string
     */
    public static function ancestor(UuidInterface $ancestor = null, bool $usesAncestor = true): string
    {
        self::$usesAncestor = $usesAncestor;
        self::$ancestor = $ancestor;

        return __CLASS__;
    }

    /**
     * Creates the Options object based on previously-supplied data
     * and resets that data for another use.
     * 
     * TODO: maybe not do this kind of thing. It's silly.
     *
     * @return Options
     */
    public static function build(): Options
    {
        $options = new Options();

        $options->setExpectedType(self::$type);
        $options->setRequired(self::$required);
        $options->setPlaceholder(self::$placeholder);
        $options->setBranches(self::$branches);
        $options->setAncestor(self::$ancestor, self::$usesAncestor);

        self::$type = Json::ANY;
        self::$required = true;
        self::$placeholder = false;
        self::$branches = false;
        self::$usesAncestor = false;
        self::$ancestor = null;

        return $options;
    }
}