<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;

/**
 * @api
 */
interface NotificationChannelInterface
{
    public function isEnabled(): bool;

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @throws CannotSendMetricsDataNotificationException
     */
    public function notify(array $metricsData, string $reportType): void;
}
