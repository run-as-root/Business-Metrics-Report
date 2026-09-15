<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api;

use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;

/**
 * @api
 */
interface MetricsCollectionStrategyInterface
{
    /**
     * @param MetricCollectorInterface[] $collectors
     * @return MetricCollectorInterface[]
     */
    public function sortCollectors(array $collectors): array;

    public function collectWithComparisons(
        MetricCollectorInterface $collector,
        DateRange $currentRange,
        DateRange $weekAgoRange,
        DateRange $monthAgoRange,
        DateRange $yearAgoRange
    ): MetricWithComparisonsInterface;
}
