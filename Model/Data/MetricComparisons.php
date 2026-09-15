<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Data;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;

class MetricComparisons implements MetricComparisonsInterface
{
    public function __construct(
        private readonly MetricResultInterface $weekAgo,
        private readonly MetricResultInterface $monthAgo,
        private readonly MetricResultInterface $yearAgo,
    ) {
    }

    public function getWeekAgo(): MetricResultInterface
    {
        return $this->weekAgo;
    }

    public function getMonthAgo(): MetricResultInterface
    {
        return $this->monthAgo;
    }

    public function getYearAgo(): MetricResultInterface
    {
        return $this->yearAgo;
    }

    public function hasAnyComparisons(): bool
    {
        return $this->weekAgo->hasValue()
            || $this->monthAgo->hasValue()
            || $this->yearAgo->hasValue();
    }
}
