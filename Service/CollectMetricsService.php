<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use RunAsRoot\BusinessMetricsReport\Api\MetricsCollectionStrategyInterface;

class CollectMetricsService
{
    /**
     * @param MetricCollectorInterface[] $collectors
     */
    public function __construct(
        private readonly array $collectors,
        private readonly MetricsCollectionStrategyInterface $metricsCollectionStrategy
    ) {
    }

    /**
     * @return MetricWithComparisonsInterface[]
     */
    public function collectAllMetrics(DateRangeSet $dateRangeSet): array
    {
        $results = [];
        $sortedCollectors = $this->metricsCollectionStrategy->sortCollectors($this->collectors);

        foreach ($sortedCollectors as $collector) {
            $results[] = $this->metricsCollectionStrategy->collectWithComparisons(
                collector: $collector,
                currentRange: $dateRangeSet->currentRange,
                weekAgoRange: $dateRangeSet->weekAgoRange,
                monthAgoRange: $dateRangeSet->monthAgoRange,
                yearAgoRange: $dateRangeSet->yearAgoRange,
            );
        }

        return $results;
    }
}
