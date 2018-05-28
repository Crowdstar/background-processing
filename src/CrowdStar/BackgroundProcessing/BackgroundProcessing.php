<?php

namespace CrowdStar\BackgroundProcessing;

use Closure;

/**
 * Class BackgroundProcessing
 *
 * @package CrowdStar\BackgroundProcessing
 */
class BackgroundProcessing
{
    /**
     * @var array
     */
    protected static $closures = [];

    /**
     * @var bool
     */
    protected static $invoked = false;

    /**
     * @return void
     * @throws Exception
     */
    public static function run()
    {
        if (self::isInvoked()) {
            throw new Exception('background process invoked already');
        }
        self::setInvoked(true);

        if (!is_callable('fastcgi_finish_request')) {
            throw new Exception('background process invokable under PHP-FPM only');
        }
        session_write_close();
        fastcgi_finish_request();

        foreach (self::$closures as $closure) {
            $closure();
        }
    }

    /**
     * This method should only be called by test scripts (e.g., PHPUnit tests) to reset states of this class during
     * repetitive tests.
     *
     * @return void
     */
    public static function reset()
    {
        self::$closures = [];
        self::$invoked  = false;
    }

    /**
     * @param Closure $op
     * @param array ...$params
     * @return void
     */
    public static function add(Closure $op, ...$params)
    {
        self::$closures[] = function () use ($op, $params) {
            return $op(...$params);
        };
    }

    /**
     * @param bool $invoked
     * @return void
     */
    protected static function setInvoked(bool $invoked)
    {
        self::$invoked = $invoked;
    }

    /**
     * @return bool
     */
    protected static function isInvoked(): bool
    {
        return self::$invoked;
    }
}
