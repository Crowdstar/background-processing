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

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * Class DockerizedTest
 *
 * @package CrowdStar\Tests\BackgroundProcessing
 * @group dockerized
 */
class DockerizedTest extends TestCase
{
    public static function dataRun(): array
    {
        return [
            [
                'expectedHttpResponse' => '',
                'expectedFinalValue'   => '',
                'start' => '0',
                'end'   => '0',
                'desc'  => 'Start and end value are empty (string "0"); cached value is always an empty string.',
            ],
            [
                'expectedHttpResponse' => '',
                'expectedFinalValue'   => '1',
                'start' => '0',
                'end'   => '1',
                'desc'  => 'Start value is empty ("0") but end value is "1"; cached value is updated accordingly.',
            ],
            [
                'expectedHttpResponse' => '1',
                'expectedFinalValue'   => '1',
                'start' => '1',
                'end'   => '0',
                'desc'  => 'Start value is "1" but end value is empty (string "0"); cached value is not updated.',
            ],
            [
                'expectedHttpResponse' => '1',
                'expectedFinalValue'   => '2',
                'start' => '1',
                'end'   => '2',
                'desc'  => 'Start value is "1" but end value is "2"; cached value is updated accordingly.',
            ],
        ];
    }

    /**
     * @dataProvider dataRun
     * @covers BackgroundProcessing::run()
     */
    public function testRun(string $expectedHttpResponse, string $expectedFinalValue, string $start, string $end): void
    {
        $client = new Client(['base_uri' => 'http://web']);
        $client->get('/', ['query' => ['reset' => true]]); // Wipe cached data.

        $this->assertSame(
            $expectedHttpResponse,
            (string) $client->get('/', ['query' => ['start' => $start, 'end' => $end]])->getBody(),
            "HTTP response should be {$expectedHttpResponse} while final value in APCu should be {$expectedFinalValue}"
        );
        $this->assertSame(
            $expectedFinalValue,
            (string) $client->get('/')->getBody(),
            "Final value in APCu should be {$expectedFinalValue} while HTTP response should be {$expectedHttpResponse}"
        );
    }
}
