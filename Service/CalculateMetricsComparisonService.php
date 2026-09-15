<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;

class CalculateMetricsComparisonService
{
    public function compare(
        MetricResultInterface $current,
        MetricResultInterface $historical,
        string $comparisonPeriod
    ): ?ComparisonResult {
        if (!$historical->hasValue()) {
            return null;
        }

        $historicalValue = (float) $historical->getValue();
        $currentValue = (float) $current->getValue();

        $percentageChange = match (true) {
            $historicalValue !== 0.0 => (($currentValue - $historicalValue) / $historicalValue) * 100,
            $currentValue === 0.0 => 0.0,
            default => null,
        };

        return new ComparisonResult(
            $percentageChange,
            $current->getFormattedValue(),
            $historical->getFormattedValue(),
            $comparisonPeriod
        );
    }
}
