<?php
/**************************************************************************
 * Copyright 2018 Glu Mobile Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *************************************************************************/

declare(strict_types=1);

namespace CrowdStar\BackgroundProcessing;

use Closure;
use CrowdStar\BackgroundProcessing\Timer\AbstractTimer;

/**
 * Class BackgroundProcessing
 *
 * @package CrowdStar\BackgroundProcessing
 */
class BackgroundProcessing
{
    /**
     * @var Closure[]
     */
    protected static $closures = [];

    /**
     * @var AbstractTimer[]
     */
    protected static $timers = [];

    /**
     * @var bool
     */
    protected static $invoked = false;

    /**
     * @param bool $stopTiming Stop timing the current transaction or not before starting processing tasks in background
     * @throws Exception
     */
    public static function run(bool $stopTiming = false): void
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

        if ($stopTiming) {
            // Stop timing the current transaction before starting processing tasks in background.
            foreach (self::$timers as $timer) {
                $timer->stopTiming();
            }
        }

        foreach (self::$closures as $closure) {
            $closure();
        }
    }

    /**
     * This method should only be called by test scripts (e.g., PHPUnit tests) to reset states of this class during
     * repetitive tests.
     */
    public static function reset(): void
    {
        self::$closures = [];
        self::$invoked  = false;
    }

    public static function add(Closure $op, ...$params): void
    {
        self::$closures[] = function () use ($op, $params) {
            return $op(...$params);
        };
    }

    public static function addTimer(AbstractTimer $timer): void
    {
        self::$timers[] = $timer;
    }

    protected static function setInvoked(bool $invoked): void
    {
        self::$invoked = $invoked;
    }

    protected static function isInvoked(): bool
    {
        return self::$invoked;
    }
}
