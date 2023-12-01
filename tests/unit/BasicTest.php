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

use PHPUnit\Framework\TestCase;

/**
 * Class BasicTest
 *
 * @package CrowdStar\Tests\BackgroundProcessing
 */
class BasicTest extends TestCase
{
    /**
     * @var int
     */
    protected static $counter = 0;

    /**
     * @inheritdoc
     */
    public static function tearDownAfterClass(): void
    {
        self::$counter = 0;
        BackgroundProcessing::reset();
        parent::tearDownAfterClass();
    }

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        self::$counter = 0;
        BackgroundProcessing::reset();
    }

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
     * @dataProvider dataRun
     * @covers BackgroundProcessing::run()
     * @throws \CrowdStar\BackgroundProcessing\Exception
     */
    public function testRun(int $expected, array $closures, string $message): void
    {
        foreach ($closures as $closure) {
            BackgroundProcessing::add(...$closure);
        }
        BackgroundProcessing::run();
        $this->assertSame($expected, self::$counter, $message);
    }

    /**
     * @covers BackgroundProcessing::run()
     */
    public function testRunWhenInvokedAlready(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('background process invoked already');

        BackgroundProcessing::run();
        BackgroundProcessing::run();
    }
}
