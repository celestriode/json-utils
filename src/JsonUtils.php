<?php namespace Celestriode\JsonUtils;

use Seld\JsonLint\JsonParser;

class JsonUtils
{
    public const UNKNOWN_TYPE = 'unknown';

    /**
     * Lints and parses the raw JSON string using Seld\JsonLint.
     * 
     * If the result is valid but null, will return an empty object instead.
     *
     * @param string $raw The JSON string to parse.
     * @return Json
     */
    public static function deserialize(string $raw): Json
    {
        $parser = new JsonParser();

        $parsed = $parser->parse($raw, JsonParser::DETECT_KEY_CONFLICTS);

        // https://github.com/Seldaek/jsonlint/blob/master/src/Seld/JsonLint/JsonParser.php
        // stringInterpolation()
        // Transforms \u0027 into &#x27; instead of the required '
        // This screws over everything. Have to use json_decode instead. Still using the parser for linting.

        return new Json(null, json_decode($raw));
    }

    /**
     * Serialize the object into a JSON string. Optionally prettified.
     * 
     * TODO: actually have serializing functions. Don't need them right now but might in the future.
     *
     * @param Json $json The object to turn into a JSON string.
     * @return string
     */
    public static function serialize(Json $json, bool $pretty = false): string
    {
        return json_encode($json->getValue(), $pretty ? JSON_PRETTY_PRINT : 0);
    }

    /**
     * Turns a stringified type (obtained via gettype()) into a
     * numeric value as used by this package.
     *
     * @param string $type The datatype to turn into a number.
     * @return integer
     */
    public static function normalizeTypeString(string $type): int
    {
        switch ($type) {
            case 'boolean':
                return Json::BOOLEAN;
            case 'integer':
                return Json::INTEGER;
            case 'double':
                return Json::DOUBLE;
            case 'string':
                return Json::STRING;
            case 'array':
                return Json::ARRAY;
            case 'object':
                return Json::OBJECT;
            case 'NULL':
                return Json::NULL;
            default:
                return Json::ANY;
        }
    }

    /**
     * Turns an integer type (obtained via normalizeTypeString) into an
     * array of string values.
     *
     * @param integer $type The datatype to turn into a string.
     * @return array
     */
    public static function normalizeTypeInteger(int $type): array
    {
        $buffer = [];

        if (($type & Json::INTEGER) !== 0) {

            $buffer[] = 'integer';
        }

        if (($type & Json::DOUBLE) !== 0) {

            $buffer[] = 'double';
        }

        if (($type & Json::STRING) !== 0) {

            $buffer[] = 'string';
        }

        if (($type & Json::BOOLEAN) !== 0) {

            $buffer[] = 'boolean';
        }

        if (($type & Json::ARRAY) !== 0) {

            $buffer[] = 'array';
        }

        if (($type & Json::OBJECT) !== 0) {

            $buffer[] = 'object';
        }

        if (($type & Json::NULL) !== 0) {

            $buffer[] = 'null';
        }

        if (empty($buffer)) {

            return [self::UNKNOWN_TYPE];
        }

        return $buffer;
    }
}