<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Data;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;

class MetricWithComparisons implements MetricWithComparisonsInterface
{
    public function __construct(
        private readonly string $metricCode,
        private readonly string $metricLabel,
        private readonly MetricResultInterface $current,
        private readonly MetricComparisonsInterface $comparisons,
    ) {
    }

    public function getMetricCode(): string
    {
        return $this->metricCode;
    }

    public function getMetricLabel(): string
    {
        return $this->metricLabel;
    }

    public function getCurrent(): MetricResultInterface
    {
        return $this->current;
    }

    public function getComparisons(): MetricComparisonsInterface
    {
        return $this->comparisons;
    }
}
