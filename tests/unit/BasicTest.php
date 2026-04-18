<?php

/**
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
 */

declare(strict_types=1);

namespace CrowdStar\BackgroundProcessing;

use CrowdStar\BackgroundProcessing\Exception\AlreadyInvokedException;
use CrowdStar\BackgroundProcessing\Exception\InvalidEnvironmentException;
use CrowdStar\Reflection\Reflection;
use PHPUnit\Framework\TestCase;

/**
 * Class BasicTest
 *
 * @internal
 * @coversNothing
 */
class BasicTest extends TestCase
{
    /**
     * @var int
     */
    protected static $counter = 0;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        self::$counter = 0;

        // Reset states of class BackgroundProcessing to make sure that each test is independent and does not affect others.
        Reflection::setProperty(BackgroundProcessing::class, 'closures', []);
        Reflection::setProperty(BackgroundProcessing::class, 'timers', []);
        Reflection::setProperty(BackgroundProcessing::class, 'invoked', false);
    }

    /**
     * @param array<array{0: \Closure, ?mixed}> $closures
     * @dataProvider dataRun
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     * @throws AlreadyInvokedException|InvalidEnvironmentException
     */
    public function testRun(int $expected, array $closures, string $message): void
    {
        foreach ($closures as $closure) {
            BackgroundProcessing::add(...$closure);
        }
        BackgroundProcessing::run();
        self::assertSame($expected, self::$counter, $message);
    }

    /**
     * @return array<array<mixed>>
     */
    public static function dataRun(): array
    {
        $closure = function (int ...$params) {
            self::$counter += array_sum($params);
        };

        return [
            [
                7,
                [
                    [
                        $closure,
                        1,
                        2,
                        4,
                    ],
                ],
                '0 + ((1 + 2 + 4)) = 7',
            ],
            [
                15,
                [
                    [
                        $closure,
                    ],
                    [
                        $closure,
                        1,
                        2,
                        4,
                    ],
                    [
                        $closure,
                        8,
                    ],
                ],
                '0 + (0 + (1 + 2 + 4) + 8) = 15',
            ],
        ];
    }

    /**
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testRunWhenInvokedAlready(): void
    {
        $this->expectException(AlreadyInvokedException::class);
        $this->expectExceptionMessage('background process invoked already');

        BackgroundProcessing::run();
        BackgroundProcessing::run();
    }

    /**
     * Verify that timers do not have stopTiming() called when $stopTiming is false (the default).
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testTimerIsNotCalledWhenStopTimingIsFalse(): void
    {
        $timer = new \MockedTimer();
        BackgroundProcessing::addTimer($timer);
        BackgroundProcessing::run();
        self::assertFalse($timer->wasCalled, 'Method AbstractTimer::stopTiming() should not be called when $stopTiming is false');
    }

    /**
     * Verify that timers have stopTiming() called when $stopTiming is true.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testTimerIsCalledWhenStopTimingIsTrue(): void
    {
        $timer = new \MockedTimer();
        BackgroundProcessing::addTimer($timer);
        BackgroundProcessing::run(true);
        self::assertTrue($timer->wasCalled, 'Method AbstractTimer::stopTiming() should be called when $stopTiming is true');
    }
}
