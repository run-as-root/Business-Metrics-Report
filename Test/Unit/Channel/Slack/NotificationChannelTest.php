<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\Client;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\NotificationChannel;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Slack\SlackReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\SummaryMessageBuilder;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\Exception\SlackApiException;
use RunAsRoot\BusinessMetricsReport\Service\FluctuationDetector;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotificationChannelTest extends TestCase
{
    private Client|MockObject $client;
    private Config|MockObject $config;
    private FluctuationDetector|MockObject $fluctuationDetector;
    private SummaryMessageBuilder|MockObject $summaryMessageBuilder;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->config = $this->createMock(Config::class);
        $this->fluctuationDetector = $this->createMock(FluctuationDetector::class);
        $this->summaryMessageBuilder = $this->createMock(SummaryMessageBuilder::class);
    }

    private function makeSut(array $formatters = []): NotificationChannel
    {
        return new NotificationChannel(
            $this->client,
            $formatters,
            $this->config,
            $this->fluctuationDetector,
            $this->summaryMessageBuilder
        );
    }

    public function testIsEnabledDelegatesToConfig(): void
    {
        $this->config->method('isSlackEnabled')->willReturn(true);

        $this->assertTrue($this->makeSut()->isEnabled());
    }

    public function testIsDisabledDelegatesToConfig(): void
    {
        $this->config->method('isSlackEnabled')->willReturn(false);

        $this->assertFalse($this->makeSut()->isEnabled());
    }

    public function testThrowsWhenChannelIdNotConfigured(): void
    {
        $this->config->method('getChannelId')->willReturn(null);
        $this->client->expects($this->never())->method('post');

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $this->makeSut()->notify([], 'daily');
    }

    public function testThrowsWhenNoFormatterFound(): void
    {
        $this->config->method('getChannelId')->willReturn('C123');
        $this->client->expects($this->never())->method('post');

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $this->makeSut()->notify([], 'daily');
    }

    public function testThrowsWhenSummaryMessageFails(): void
    {
        $this->config->method('getChannelId')->willReturn('C123');
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(false);
        $this->summaryMessageBuilder->method('build')->willReturn(['blocks' => []]);
        $this->client->method('post')->willThrowException(new SlackApiException('channel_not_found'));

        $formatter = $this->createMock(SlackReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $this->expectExceptionMessage('Failed to send Slack summary message: channel_not_found');
        $this->makeSut([$formatter])->notify([], 'daily');
    }

    public function testThrowsWhenFormatterFails(): void
    {
        $this->config->method('getChannelId')->willReturn('C123');
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(false);
        $this->summaryMessageBuilder->method('build')->willReturn(['blocks' => []]);
        $this->client->method('post')->willReturn('1234567890.000001');

        $formatter = $this->createMock(SlackReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willThrowException(new \RuntimeException('Format error'));

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $this->makeSut([$formatter])->notify([], 'daily');
    }

    public function testThrowsWhenThreadReplyFails(): void
    {
        $this->config->method('getChannelId')->willReturn('C123');
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(false);
        $this->summaryMessageBuilder->method('build')->willReturn(['blocks' => []]);
        $callCount = 0;
        $this->client->method('post')->willReturnCallback(
            static function () use (&$callCount): string {
                if (++$callCount === 2) {
                    throw new SlackApiException('invalid_auth');
                }
                return '1234567890.000001';
            }
        );

        $formatter = $this->createMock(SlackReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willReturn(['blocks' => []]);

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $this->expectExceptionMessage('Failed to send Slack thread reply: invalid_auth');
        $this->makeSut([$formatter])->notify([], 'daily');
    }

    public function testSuccessfulNotifySendsSummaryThenThread(): void
    {
        $channelId = 'C123ABC';
        $messageTs = '1234567890.000001';
        $summaryBlocks = ['blocks' => [['type' => 'header']]];
        $threadBlocks = ['blocks' => [['type' => 'section']]];

        $this->config->method('getChannelId')->willReturn($channelId);
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(false);
        $this->summaryMessageBuilder->method('build')->with('daily', false)->willReturn($summaryBlocks);

        $expectedCalls = [
            [$summaryBlocks + ['channel' => $channelId]],
            [$threadBlocks + ['channel' => $channelId, 'thread_ts' => $messageTs]],
        ];
        $callIndex = 0;
        $this->client->expects($this->exactly(2))->method('post')
            ->willReturnCallback(
                static function (array $payload) use ($expectedCalls, &$callIndex, $messageTs): string {
                    self::assertSame($expectedCalls[$callIndex++][0], $payload);
                    return $messageTs;
                }
            );

        $formatter = $this->createMock(SlackReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willReturn($threadBlocks);

        $this->makeSut([$formatter])->notify(
            [$this->createMock(MetricWithComparisonsInterface::class)],
            'daily'
        );
    }

    public function testFluctuationIsPropagatedToSummaryBuilder(): void
    {
        $this->config->method('getChannelId')->willReturn('C123');
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(true);
        $this->client->method('post')->willReturnOnConsecutiveCalls('123.456', '123.456');

        $this->summaryMessageBuilder->expects($this->once())
            ->method('build')
            ->with('daily', true)
            ->willReturn(['blocks' => []]);

        $formatter = $this->createMock(SlackReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willReturn(['blocks' => []]);

        $this->makeSut([$formatter])->notify([], 'daily');
    }
}
