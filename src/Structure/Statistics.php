<?php namespace Celestriode\JsonUtils\Structure;

class Statistics
{
    public $statistics = [];

    /**
     * Changes the raw statistics in their entirety.
     *
     * @param array $statistics The statistics to overwrite with.
     * @return void
     */
    public function setRawStatistics(array $statistics): void
    {
        $this->statistics = $statistics;
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
    public function setStat(float $value, string ...$pathToStat): void
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
