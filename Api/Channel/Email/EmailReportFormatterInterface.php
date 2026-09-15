<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;

/**
 * @api
 */
interface EmailReportFormatterInterface
{
    public function getReportType(): string;

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function format(array $metricsData): EmailFormattedReportInterface;
}
