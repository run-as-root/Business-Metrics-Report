<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Channel\Slack\Client;
use RunAsRoot\BusinessMetricsReport\Exception\SlackApiException;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private Curl|MockObject $curl;
    private Json|MockObject $json;
    private Config|MockObject $config;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $this->json = $this->createMock(Json::class);
        $this->config = $this->createMock(Config::class);
    }

    private function makeSut(): Client
    {
        return new Client($this->curl, $this->json, $this->config);
    }

    public function testThrowsWhenBotTokenNotConfigured(): void
    {
        $this->config->method('getBotToken')->willReturn(null);
        $this->curl->expects($this->never())->method('post');

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('Slack bot token is not configured');
        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testThrowsOnHttpError(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->curl->method('getStatus')->willReturn(500);
        $this->curl->method('getBody')->willReturn('error');

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('Slack API returned unexpected HTTP status 500');
        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testThrowsWhenResponseIsNotArray(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->json->method('unserialize')->willReturn('not-an-array');
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('"not-an-array"');

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('Unexpected Slack API response format');
        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testThrowsWhenSlackResponseNotOk(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->json->method('unserialize')->willReturn(['ok' => false, 'error' => 'channel_not_found']);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"ok":false}');

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('Slack API returned an error: channel_not_found');
        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testThrowsWhenTsMissing(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->json->method('unserialize')->willReturn(['ok' => true]);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"ok":true}');

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('Slack API response missing message timestamp');
        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testReturnsTsOnSuccess(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->json->method('unserialize')->willReturn(['ok' => true, 'ts' => '1234567890.000001']);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"ok":true,"ts":"1234567890.000001"}');

        $this->assertSame('1234567890.000001', $this->makeSut()->post(['channel' => 'C123']));
    }

    public function testAddsAuthorizationHeader(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-test-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->json->method('unserialize')->willReturn(['ok' => true, 'ts' => '123.456']);
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"ok":true,"ts":"123.456"}');

        $expectedHeaders = [
            ['Content-Type', 'application/json'],
            ['Authorization', 'Bearer xoxb-test-token'],
        ];
        $callIndex = 0;
        $this->curl->expects($this->exactly(2))->method('addHeader')
            ->willReturnCallback(
                static function (string $name, string $value) use ($expectedHeaders, &$callIndex): void {
                    [$expectedName, $expectedValue] = $expectedHeaders[$callIndex++];
                    self::assertSame($expectedName, $name);
                    self::assertSame($expectedValue, $value);
                }
            );

        $this->makeSut()->post(['channel' => 'C123']);
    }

    public function testThrowsOnCurlException(): void
    {
        $this->config->method('getBotToken')->willReturn('xoxb-token');
        $this->json->method('serialize')->willReturn('{}');
        $this->curl->method('post')->willThrowException(new \RuntimeException('Connection refused'));

        $this->expectException(SlackApiException::class);
        $this->expectExceptionMessage('HTTP request to Slack failed: Connection refused');
        $this->makeSut()->post(['channel' => 'C123']);
    }
}
