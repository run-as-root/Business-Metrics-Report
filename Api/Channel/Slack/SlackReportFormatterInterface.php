<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;

/**
 * @api
 */
interface SlackReportFormatterInterface
{
    public function getReportType(): string;

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @return array<string, mixed>
     */
    public function format(array $metricsData): array;
}
