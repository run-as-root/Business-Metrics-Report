<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use RunAsRoot\BusinessMetricsReport\Api\MetricsCollectionStrategyInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;

class MetricsCollectionStrategy implements MetricsCollectionStrategyInterface
{
    public function __construct(
        private readonly MetricWithComparisonsInterfaceFactory $metricWithComparisonsFactory,
        private readonly MetricComparisonsInterfaceFactory $metricComparisonsFactory,
    ) {
    }

    /**
     * @param MetricCollectorInterface[] $collectors
     * @return MetricCollectorInterface[]
     */
    public function sortCollectors(array $collectors): array
    {
        usort($collectors, static fn ($a, $b) => $a->getSortOrder() <=> $b->getSortOrder());
        return $collectors;
    }

    public function collectWithComparisons(
        MetricCollectorInterface $collector,
        DateRange $currentRange,
        DateRange $weekAgoRange,
        DateRange $monthAgoRange,
        DateRange $yearAgoRange
    ): MetricWithComparisonsInterface {
        $current = $collector->collect($currentRange->startDate, $currentRange->endDate);

        return $this->metricWithComparisonsFactory->create([
            'metricCode' => $collector->getMetricCode(),
            'metricLabel' => $collector->getMetricLabel(),
            'current' => $current,
            'comparisons' => $this->metricComparisonsFactory->create([
                'weekAgo' => $this->collectForDateRange($collector, $weekAgoRange),
                'monthAgo' => $this->collectForDateRange($collector, $monthAgoRange),
                'yearAgo' => $this->collectForDateRange($collector, $yearAgoRange),
            ]),
        ]);
    }

    private function collectForDateRange(
        MetricCollectorInterface $collector,
        DateRange $dateRange
    ): MetricResultInterface {
        return $collector->collect($dateRange->startDate, $dateRange->endDate);
    }
}
