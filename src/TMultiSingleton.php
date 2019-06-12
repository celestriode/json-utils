<?php namespace Celestriode\JsonUtils;

trait TMultiSingleton
{
    private static $instances = [];

    /**
     * Returns a singleton of the class.
     *
     * @param mixed ...$data Extra data that may be used by the class.
     * @return self
     */
    final public static function instance(...$data): self
    {
        // Return the instantiated class if existent.

        if (isset(self::$instances[static::class])) {

            return self::$instances[static::class];
        }

        // Create, store, and return a new instance.

        return self::$instances[static::class] = new static(...$data);
    }
}