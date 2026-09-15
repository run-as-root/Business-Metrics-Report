<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;

class SummaryTextFormatter
{
    public function __construct(
        private readonly CalculateMetricsComparisonService $comparisonService
    ) {
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function formatSummary(array $metricsData): string
    {
        $summaryParts = [];

        foreach ($metricsData as $metricData) {
            $currentMetric = $metricData->getCurrent();
            $comparisons = $metricData->getComparisons();

            $comparison = $this->comparisonService->compare($currentMetric, $comparisons->getWeekAgo(), 'week ago');
            $percentageChange = $comparison?->getPercentageChange();
            if ($comparison !== null && $percentageChange !== null) {
                $summaryParts[] = sprintf(
                    '%s: %s',
                    $metricData->getMetricLabel(),
                    $this->interpret($percentageChange)
                );
            }
        }

        return implode(' | ', $summaryParts);
    }

    private function interpret(float $percentageChange): string
    {
        if ($percentageChange > 5.0) {
            return 'Strong growth';
        }

        if ($percentageChange > 0.0) {
            return 'Slight increase';
        }

        if ($percentageChange >= -5.0) {
            return 'Slight decrease';
        }

        return 'Notable decline';
    }
}
