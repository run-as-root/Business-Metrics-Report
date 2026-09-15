<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Cron;

use RunAsRoot\BusinessMetricsReport\Api\ReportType;
use RunAsRoot\BusinessMetricsReport\Service\CollectMetricsService;
use RunAsRoot\BusinessMetricsReport\Api\DateRangeServiceInterface;
use RunAsRoot\BusinessMetricsReport\Service\NotificationDispatcher;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use Psr\Log\LoggerInterface;

class SendWeeklyMetricsNotification
{
    public function __construct(
        private readonly Config $config,
        private readonly CollectMetricsService $collectMetricsService,
        private readonly DateRangeServiceInterface $dateRangeService,
        private readonly NotificationDispatcher $notificationDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isWeeklyEnabled()) {
            return;
        }

        $metricsData = $this->collectMetricsService->collectAllMetrics(
            $this->dateRangeService->getWeeklySet()
        );

        if (empty($metricsData)) {
            return;
        }

        $result = $this->notificationDispatcher->dispatch($metricsData, ReportType::WEEKLY);

        foreach ($result->getErrors() as $error) {
            $this->logger->error($error->getMessage(), [
                'channel' => $error->getChannelClass(),
                'report_type' => $error->getReportType(),
                'exception' => $error,
            ]);
        }
    }
}
