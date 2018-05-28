<?php

namespace CrowdStar\Tests\BackgroundProcessing;

use CrowdStar\BackgroundProcessing\BackgroundProcessing;
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
    /**
     * @return array
     */
    public function dataRun(): array
    {
        return [
            [
                'a',
                'b',
                'a',
                'b',
            ],
        ];
    }

    /**
     * @dataProvider dataRun
     * @covers BackgroundProcessing::run()
     * @param string $expectedHttpResponse
     * @param string $expectedFinalValue
     * @param string $start
     * @param string $end
     */
    public function testRun(string $expectedHttpResponse, string $expectedFinalValue, string $start, string $end)
    {
        $client = new Client(['base_uri' => 'http://127.0.0.1']);
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
