<?php namespace Celestriode\JsonUtils\Structure;

use Celestriode\JsonUtils\JsonUtils;
use Celestriode\JsonUtils\Json;

class Statistics
{
    protected $statistics = [
        'keys' => [],
        'datatypes' => [],
        'elements' => [
            'total' => 0
        ],
        'fields' => [
            'total' => 0
        ],
        'root' => [
            'datatype' => 'unknown'
        ]
    ];

    /**
     * Adds values from Json into the statistics array.
     *
     * @param Json $json The Json to base statistics off of.
     * @return void
     */
    public function addJsonToStats(Json $json): void
    {
        // If the Json has no parent, assume it's the root.

        if ($json->getParent() === null) {

            $this->incrementRoot($json);
            return;
        }

        // Key name & value counts.

        $this->incrementKeyValue($json);

        // Element counts.

        $this->incrementElementCounts($json);

        // Child counts.

        $this->incrementChildCounts($json);

        // Datatype counts.

        $this->incrementDatatypes($json->getType());
    }

    protected function incrementRoot(Json $json): void
    {
        $this->statistics['root']['datatype'] = implode('/', JsonUtils::normalizeTypeInteger($json->getType()));

        if ($json->isType(Json::ARRAY)) {

            $this->statistics['root']['children'] = $json->getElements()->count();
        }

        if ($json->isType(Json::OBJECT)) {

            $this->statistics['root']['children'] = $json->getFields()->count();
        }
    }

    /**
     * Increments total key count and relevant values if applicable.
     *
     * @param Json $json The Json to base statistics off of.
     * @return void
     */
    protected function incrementKeyValue(Json $json): void
    {
        $key = json_encode($json->getKey());

        if ($json->isType(Json::SCALAR)) {

            // If scalar, store total and the actual value.

            $this->addStat(1, 'keys', $key, 'scalar', 'total');
            $this->addStat(1, 'keys', $key, 'scalar', 'values', $json->getValue());
        } else if ($json->isType(Json::OBJECT)) {

            // If object, store total.

            $this->addStat(1, 'keys', $key, 'object', 'total');
        } else if ($json->isType(Json::ARRAY)) {

            // If array, store total.

            $this->addStat(1, 'keys', $key, 'array', 'total');
        } else if ($json->isType(Json::NULL)) {

            // If null, store total.

            $this->addStat(1, 'keys', $key, 'null', 'total');
        } else {

            // If none of the above, unknown datatype.

            $this->addStat(1, 'keys', $key, JsonUtils::UNKNOWN_TYPE, 'total');
        }
    }

    /**
     * Add 1 to "datatypes.<type>"
     *
     * @param integer $type The datatype of the Json.
     * @return void
     */
    protected function incrementDatatypes(int $type): void
    {
        $typeName = implode('/', JsonUtils::normalizeTypeInteger($type));

        $this->addStat(1, 'datatypes', $typeName);
    }

    /**
     * If the parent is an array, add 1 to "elements.total"
     *
     * @param Json $json The Json to base statistics off of.
     * @return void
     */
    protected function incrementElementCounts(Json $json): void
    {
        if ($json->getParent() !== null && $json->getParent()->isType(Json::ARRAY)) {

            $this->addStat(1, 'elements', 'total');
        }
    }

    /**
     * If the parent is an object, add 1 to "children.total"
     *
     * @param Json $json The Json to base statistics off of.
     * @return void
     */
    protected function incrementChildCounts(Json $json): void
    {
        if ($json->getParent() !== null && $json->getParent()->isType(Json::OBJECT)) {

            $this->addStat(1, 'fields', 'total');
        }
    }

    /**
     * Adds the specified value onto the current value of the
     * specified statistic.
     *
     * @param float $amount The amount to add to the stat.
     * @param string ...$pathToStat The path to the statistic.
     * @return void
     */
    public function addStat(float $amount, string ...$pathToStat): void
    {
        $amount += $this->getStat(...$pathToStat);

        $this->setStat($amount, ...$pathToStat);
    }

    /**
     * Returns the value of the specified statistic.
     *
     * @param string ...$pathToStat The path to the statistic.
     * @return float
     */
    public function getStat(string ...$pathToStat): float
    {
        $current = $this->getStatistics();

        for ($i = 0, $j = count($pathToStat); $i < $j; $i++) {

            // If the stat exists...

            if (isset($current[$pathToStat[$i]])) {

                // If the stat itself is numeric, return it.

                if (is_numeric($current[$pathToStat[$i]])) {

                    return $current[$pathToStat[$i]];
                }

                // Otherwise set the current to the stat's value.

                $current = $current[$pathToStat[$i]];
            } else {

                // No more children, return 0 because it wasn't found.

                return 0;
            }
        }

        // Not sure how this is possible.

        return 0;
    }

    /**
     * Sets a statistic value nested along the specified path.
     *
     * @param float $value The value to set.
     * @param string ...$pathToStat The path to the statistic.
     * @return void
     */
    function setStat(float $value, string ...$pathToStat): void
    {
        // Set the root as a direct reference to the array.

        $stat = &$this->statistics;

        // Cycle through each path.

        for ($i = 0, $j = count($pathToStat); $i < $j; $i++) {

            // Set the root to a reference within the reference.

            $stat = &$stat[$pathToStat[$i]];
        }

        // Changing the reference within the reference will change the original as well because that's how it works.

        $stat = $value;
    }

    /**
     * Returns the array of statistics.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }
}
