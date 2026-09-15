<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Cron;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Cron\SendWeeklyMetricsNotification;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;
use RunAsRoot\BusinessMetricsReport\Model\Data\NotificationDispatchingResult;
use RunAsRoot\BusinessMetricsReport\Service\CollectMetricsService;
use RunAsRoot\BusinessMetricsReport\Service\DateRangeService;
use RunAsRoot\BusinessMetricsReport\Service\NotificationDispatcher;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SendWeeklyMetricsNotificationTest extends TestCase
{
    private Config|MockObject $config;
    private CollectMetricsService|MockObject $collectMetricsService;
    private DateRangeService|MockObject $dateRangeService;
    private NotificationDispatcher|MockObject $notificationDispatcher;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->collectMetricsService = $this->createMock(CollectMetricsService::class);
        $this->dateRangeService = $this->createMock(DateRangeService::class);
        $this->notificationDispatcher = $this->createMock(NotificationDispatcher::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testExecuteDoesNothingWhenWeeklyIsDisabled(): void
    {
        $this->config->method('isWeeklyEnabled')->willReturn(false);
        $this->collectMetricsService->expects($this->never())->method('collectAllMetrics');
        $this->notificationDispatcher->expects($this->never())->method('dispatch');

        $this->makeSut()->execute();
    }

    public function testExecuteDoesNotDispatchWhenNoMetricsCollected(): void
    {
        $this->config->method('isWeeklyEnabled')->willReturn(true);
        $this->dateRangeService->method('getWeeklySet')->willReturn($this->makeDateRangeSet());
        $this->collectMetricsService->method('collectAllMetrics')->willReturn([]);
        $this->notificationDispatcher->expects($this->never())->method('dispatch');

        $this->makeSut()->execute();
    }

    public function testExecuteDispatchesWithWeeklyReportType(): void
    {
        $metrics = [$this->createMock(MetricWithComparisonsInterface::class)];
        $this->config->method('isWeeklyEnabled')->willReturn(true);
        $this->dateRangeService->method('getWeeklySet')->willReturn($this->makeDateRangeSet());
        $this->collectMetricsService->method('collectAllMetrics')->willReturn($metrics);

        $this->notificationDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($metrics, 'weekly')
            ->willReturn(new NotificationDispatchingResult([]));

        $this->makeSut()->execute();
    }

    public function testExecuteUsesTheWeeklyDateRangeSet(): void
    {
        $dateRangeSet = $this->makeDateRangeSet();
        $this->config->method('isWeeklyEnabled')->willReturn(true);
        $this->dateRangeService->expects($this->once())->method('getWeeklySet')->willReturn($dateRangeSet);
        $this->collectMetricsService->expects($this->once())
            ->method('collectAllMetrics')
            ->with($dateRangeSet)
            ->willReturn([]);

        $this->makeSut()->execute();
    }

    public function testExecuteLogsErrorsFromResult(): void
    {
        $metrics = [$this->createMock(MetricWithComparisonsInterface::class)];
        $this->config->method('isWeeklyEnabled')->willReturn(true);
        $this->dateRangeService->method('getWeeklySet')->willReturn($this->makeDateRangeSet());
        $this->collectMetricsService->method('collectAllMetrics')->willReturn($metrics);

        $error = new CannotSendMetricsDataNotificationException('SomeChannel', 'weekly', 'Send failed');
        $this->notificationDispatcher->method('dispatch')->willReturn(new NotificationDispatchingResult([$error]));
        $this->logger->expects($this->once())->method('error');

        $this->makeSut()->execute();
    }

    private function makeSut(): SendWeeklyMetricsNotification
    {
        return new SendWeeklyMetricsNotification(
            $this->config,
            $this->collectMetricsService,
            $this->dateRangeService,
            $this->notificationDispatcher,
            $this->logger,
        );
    }

    private function makeDateRangeSet(): DateRangeSet
    {
        $range = new DateRange(
            startDate: new \DateTimeImmutable('2025-01-01 00:00:00'),
            endDate: new \DateTimeImmutable('2025-01-01 23:59:59'),
        );
        return new DateRangeSet($range, $range, $range, $range);
    }
}
