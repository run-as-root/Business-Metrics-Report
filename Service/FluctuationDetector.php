<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;

class FluctuationDetector
{
    public function __construct(
        private readonly CalculateMetricsComparisonService $comparisonService,
        private readonly Config $config,
    ) {
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function hasFluctuation(array $metricsData): bool
    {
        $threshold = $this->config->getFluctuationDeclineThreshold();

        foreach ($metricsData as $metricData) {
            $comparison = $this->comparisonService->compare(
                $metricData->getCurrent(),
                $metricData->getComparisons()->getWeekAgo(),
                'week ago'
            );

            $percentageChange = $comparison?->getPercentageChange();

            if ($percentageChange !== null && $percentageChange < $threshold) {
                return true;
            }
        }

        return false;
    }
}
