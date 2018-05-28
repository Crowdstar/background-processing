<?php

namespace CrowdStar\Tests\BackgroundProcessing;

use CrowdStar\BackgroundProcessing\BackgroundProcessing;
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
    public static function tearDownAfterClass()
    {
        self::$counter = 0;
        BackgroundProcessing::reset();
        parent::tearDownAfterClass();
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        self::$counter = 0;
        BackgroundProcessing::reset();
    }

    /**
     * @return array
     */
    public function dataRun(): array
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
     * @param int $expected
     * @param array $closures
     * @param string $message
     * @throws \CrowdStar\BackgroundProcessing\Exception
     */
    public function testRun(int $expected, array $closures, string $message)
    {
        foreach ($closures as $closure) {
            BackgroundProcessing::add(...$closure);
        }
        BackgroundProcessing::run();
        $this->assertSame($expected, self::$counter, $message);
    }

    /**
     * @covers BackgroundProcessing::run()
     * @expectedException \CrowdStar\BackgroundProcessing\Exception
     * @expectedExceptionMessage background process invoked already
     */
    public function testRunWhenInvokedAlready()
    {
        BackgroundProcessing::run();
        BackgroundProcessing::run();
    }
}
