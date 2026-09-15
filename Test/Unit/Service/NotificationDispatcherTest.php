<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\NotificationChannelInterface;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\Service\FluctuationDetector;
use RunAsRoot\BusinessMetricsReport\Service\NotificationDispatcher;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NotificationDispatcherTest extends TestCase
{
    private FluctuationDetector|MockObject $fluctuationDetector;
    private Config|MockObject $config;

    protected function setUp(): void
    {
        $this->fluctuationDetector = $this->createMock(FluctuationDetector::class);
        $this->config = $this->createMock(Config::class);
    }

    /**
     * @param NotificationChannelInterface[] $channels
     */
    private function makeSut(array $channels): NotificationDispatcher
    {
        return new NotificationDispatcher($channels, $this->fluctuationDetector, $this->config);
    }

    public function testDisabledChannelIsSkipped(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(false);
        $channel->expects($this->never())->method('notify');

        $this->makeSut([$channel])->dispatch([], 'daily');
    }

    public function testEnabledChannelReceivesNotify(): void
    {
        $metricsData = [$this->createMock(MetricWithComparisonsInterface::class)];

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->expects($this->once())->method('notify')->with($metricsData, 'daily');

        $this->makeSut([$channel])->dispatch($metricsData, 'daily');
    }

    public function testFailedChannelExceptionIsCollectedInResult(): void
    {
        $exception = new CannotSendMetricsDataNotificationException('SomeChannel', 'daily', 'Send failed');

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->method('notify')->willThrowException($exception);

        $result = $this->makeSut([$channel])->dispatch([], 'daily');

        $this->assertTrue($result->hasErrors());
        $this->assertSame([$exception], $result->getErrors());
    }

    public function testResultHasNoErrorsWhenAllChannelsSucceed(): void
    {
        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);

        $result = $this->makeSut([$channel])->dispatch([], 'daily');

        $this->assertFalse($result->hasErrors());
        $this->assertSame([], $result->getErrors());
    }

    public function testMixedChannelsOnlyDispatchesToEnabledOnes(): void
    {
        $metricsData = [$this->createMock(MetricWithComparisonsInterface::class)];

        $disabledChannel = $this->createMock(NotificationChannelInterface::class);
        $disabledChannel->method('isEnabled')->willReturn(false);
        $disabledChannel->expects($this->never())->method('notify');

        $enabledChannel = $this->createMock(NotificationChannelInterface::class);
        $enabledChannel->method('isEnabled')->willReturn(true);
        $enabledChannel->expects($this->once())->method('notify')->with($metricsData, 'weekly');

        $this->makeSut([$disabledChannel, $enabledChannel])->dispatch($metricsData, 'weekly');
    }

    public function testRemainingChannelsAreDispatchedAfterOneChannelFails(): void
    {
        $failingChannel = $this->createMock(NotificationChannelInterface::class);
        $failingChannel->method('isEnabled')->willReturn(true);
        $failingChannel->method('notify')->willThrowException(
            new CannotSendMetricsDataNotificationException('FailingChannel', 'daily', 'Failed')
        );

        $succeedingChannel = $this->createMock(NotificationChannelInterface::class);
        $succeedingChannel->method('isEnabled')->willReturn(true);
        $succeedingChannel->expects($this->once())->method('notify');

        $result = $this->makeSut([$failingChannel, $succeedingChannel])->dispatch([], 'daily');

        $this->assertTrue($result->hasErrors());
        $this->assertCount(1, $result->getErrors());
    }

    public function testSkipsAllChannelsWhenOnlyOnFluctuationEnabledAndNoFluctuationDetected(): void
    {
        $this->config->method('isOnlyOnFluctuationEnabled')->willReturn(true);
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(false);

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->expects($this->never())->method('isEnabled');
        $channel->expects($this->never())->method('notify');

        $result = $this->makeSut([$channel])->dispatch([], 'daily');

        $this->assertFalse($result->hasErrors());
    }

    public function testDispatchesToChannelsWhenOnlyOnFluctuationEnabledAndFluctuationDetected(): void
    {
        $metricsData = [$this->createMock(MetricWithComparisonsInterface::class)];
        $this->config->method('isOnlyOnFluctuationEnabled')->willReturn(true);
        $this->fluctuationDetector->method('hasFluctuation')->willReturn(true);

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->expects($this->once())->method('notify')->with($metricsData, 'daily');

        $this->makeSut([$channel])->dispatch($metricsData, 'daily');
    }

    public function testDispatchesToChannelsWhenOnlyOnFluctuationDisabledRegardlessOfFluctuation(): void
    {
        $this->config->method('isOnlyOnFluctuationEnabled')->willReturn(false);
        $this->fluctuationDetector->expects($this->never())->method('hasFluctuation');

        $channel = $this->createMock(NotificationChannelInterface::class);
        $channel->method('isEnabled')->willReturn(true);
        $channel->expects($this->once())->method('notify');

        $this->makeSut([$channel])->dispatch([], 'daily');
    }
}
