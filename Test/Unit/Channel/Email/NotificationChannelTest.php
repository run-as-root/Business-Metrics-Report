<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailFormattedReportInterface;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\NotificationChannel;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Sender;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotificationChannelTest extends TestCase
{
    private Config|MockObject $config;
    private Sender|MockObject $sender;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->sender = $this->createMock(Sender::class);
    }

    public function testIsEnabledDelegatesToConfig(): void
    {
        $this->config->method('isEmailEnabled')->willReturn(true);
        $sut = new NotificationChannel([], $this->sender, $this->config);

        $this->assertTrue($sut->isEnabled());
    }

    public function testIsDisabledDelegatesToConfig(): void
    {
        $this->config->method('isEmailEnabled')->willReturn(false);
        $sut = new NotificationChannel([], $this->sender, $this->config);

        $this->assertFalse($sut->isEnabled());
    }

    public function testThrowsWhenFormatterNotFound(): void
    {
        $this->sender->expects($this->never())->method('send');
        $sut = new NotificationChannel([], $this->sender, $this->config);

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $sut->notify([], 'daily');
    }

    public function testThrowsWhenFormatterFails(): void
    {
        $formatter = $this->createMock(EmailReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willThrowException(new \RuntimeException('Format error'));

        $this->sender->expects($this->never())->method('send');
        $sut = new NotificationChannel([$formatter], $this->sender, $this->config);

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $sut->notify([], 'daily');
    }

    public function testThrowsWhenSendFails(): void
    {
        $report = $this->createMock(EmailFormattedReportInterface::class);

        $formatter = $this->createMock(EmailReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willReturn($report);

        $this->sender->method('send')->willReturn(false);

        $sut = new NotificationChannel([$formatter], $this->sender, $this->config);

        $this->expectException(CannotSendMetricsDataNotificationException::class);
        $sut->notify([$this->createMock(MetricWithComparisonsInterface::class)], 'daily');
    }

    public function testDoesNotThrowOnSuccessfulNotification(): void
    {
        $report = $this->createMock(EmailFormattedReportInterface::class);

        $formatter = $this->createMock(EmailReportFormatterInterface::class);
        $formatter->method('getReportType')->willReturn('daily');
        $formatter->method('format')->willReturn($report);

        $this->sender->method('send')->willReturn(true);

        $sut = new NotificationChannel([$formatter], $this->sender, $this->config);

        $sut->notify([$this->createMock(MetricWithComparisonsInterface::class)], 'daily');
        $this->addToAssertionCount(1);
    }
}
