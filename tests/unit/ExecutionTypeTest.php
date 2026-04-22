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

use CrowdStar\BackgroundProcessing\Exception\BackgroundProcessingFailedException;
use CrowdStar\Reflection\Reflection;
use PHPUnit\Framework\TestCase;

/**
 * Class ExecutionTypeTest
 *
 * @internal
 * @coversNothing
 */
class ExecutionTypeTest extends TestCase
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
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_STOP_ON_ERROR);
    }

    /**
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::getExecutionType()
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::setExecutionType()
     */
    public function testDefaultExecutionType(): void
    {
        self::assertSame(
            BackgroundProcessing::EXECUTION_TYPE_STOP_ON_ERROR,
            BackgroundProcessing::getExecutionType(),
            'Default execution type should be STOP_ON_ERROR'
        );
    }

    /**
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::getExecutionType()
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::setExecutionType()
     */
    public function testSetExecutionType(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);
        self::assertSame(
            BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR,
            BackgroundProcessing::getExecutionType(),
            'Execution type should be CONTINUE_ON_ERROR after setting it'
        );
    }

    /**
     * Verify that in STOP_ON_ERROR mode, the original exception is thrown directly and subsequent closures do not run.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testStopOnErrorThrowsOriginalException(): void
    {
        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            throw new \RuntimeException('first failure');
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('first failure');
        BackgroundProcessing::run();
    }

    /**
     * Verify that in STOP_ON_ERROR mode, closures after the failing one are not executed.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testStopOnErrorStopsAtFailure(): void
    {
        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            throw new \RuntimeException('failure');
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });

        try {
            BackgroundProcessing::run();
        } catch (\RuntimeException $e) {
            // expected
        }

        self::assertSame(1, self::$counter, 'Only the first closure should have executed before the failure');
    }

    /**
     * Verify that in CONTINUE_ON_ERROR mode, all closures run even when some throw exceptions.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testContinueOnErrorExecutesAllClosures(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);

        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            throw new \RuntimeException('first failure');
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            throw new \LogicException('second failure');
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });

        try {
            BackgroundProcessing::run();
        } catch (BackgroundProcessingFailedException $e) {
            // expected
        }

        self::assertSame(3, self::$counter, 'All non-failing closures should have executed');
    }

    /**
     * Verify that in CONTINUE_ON_ERROR mode, a BackgroundProcessingFailedException is thrown with all collected exceptions.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     * @covers \CrowdStar\BackgroundProcessing\Exception\BackgroundProcessingFailedException::getExceptions()
     */
    public function testContinueOnErrorThrowsAggregateException(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);

        BackgroundProcessing::add(function () {
            throw new \RuntimeException('first failure');
        });
        BackgroundProcessing::add(function () {
            throw new \LogicException('second failure');
        });

        try {
            BackgroundProcessing::run();
            self::fail('BackgroundProcessingFailedException should have been thrown');
        } catch (BackgroundProcessingFailedException $e) {
            self::assertSame('2 background processing task(s) failed', $e->getMessage());
            self::assertCount(2, $e->getExceptions());
            self::assertInstanceOf(\RuntimeException::class, $e->getExceptions()[0]);
            self::assertSame('first failure', $e->getExceptions()[0]->getMessage());
            self::assertInstanceOf(\LogicException::class, $e->getExceptions()[1]);
            self::assertSame('second failure', $e->getExceptions()[1]->getMessage());
        }
    }

    /**
     * Verify that the first collected exception is chained as the "previous" exception.
     *
     * @covers \CrowdStar\BackgroundProcessing\Exception\BackgroundProcessingFailedException::__construct()
     */
    public function testAggregateExceptionChainsFirstAsPrevious(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);

        BackgroundProcessing::add(function () {
            throw new \RuntimeException('root cause');
        });

        try {
            BackgroundProcessing::run();
            self::fail('BackgroundProcessingFailedException should have been thrown');
        } catch (BackgroundProcessingFailedException $e) {
            self::assertNotNull($e->getPrevious(), 'Previous exception should be set');
            self::assertSame('root cause', $e->getPrevious()->getMessage());
        }
    }

    /**
     * Verify that in CONTINUE_ON_ERROR mode, no exception is thrown when all closures succeed.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     */
    public function testContinueOnErrorNoExceptionWhenAllSucceed(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);

        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });

        BackgroundProcessing::run();

        self::assertSame(2, self::$counter, 'Both closures should have executed successfully');
    }

    /**
     * Verify that in CONTINUE_ON_ERROR mode with a single failure, the aggregate contains exactly one exception.
     *
     * @covers \CrowdStar\BackgroundProcessing\BackgroundProcessing::run()
     * @covers \CrowdStar\BackgroundProcessing\Exception\BackgroundProcessingFailedException::getExceptions()
     */
    public function testContinueOnErrorSingleFailure(): void
    {
        BackgroundProcessing::setExecutionType(BackgroundProcessing::EXECUTION_TYPE_CONTINUE_ON_ERROR);

        BackgroundProcessing::add(function () {
            self::$counter++;
        });
        BackgroundProcessing::add(function () {
            throw new \RuntimeException('only failure');
        });
        BackgroundProcessing::add(function () {
            self::$counter++;
        });

        try {
            BackgroundProcessing::run();
            self::fail('BackgroundProcessingFailedException should have been thrown');
        } catch (BackgroundProcessingFailedException $e) {
            self::assertSame('1 background processing task(s) failed', $e->getMessage());
            self::assertCount(1, $e->getExceptions());
            self::assertSame('only failure', $e->getExceptions()[0]->getMessage());
        }

        self::assertSame(2, self::$counter, 'Non-failing closures should have executed');
    }
}
