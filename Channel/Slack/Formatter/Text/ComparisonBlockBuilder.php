<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;

class ComparisonBlockBuilder
{
    public function __construct(
        private readonly CalculateMetricsComparisonService $comparisonService,
        private readonly ComparisonTextFormatter $comparisonTextFormatter
    ) {
    }

    public function buildDailyComparisons(
        MetricResultInterface $current,
        MetricComparisonsInterface $comparisons
    ): string {
        $lines = ['*Comparisons:*'];

        $comparison = $this->comparisonService->compare($current, $comparisons->getWeekAgo(), 'vs 1 week ago');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        $comparison = $this->comparisonService->compare($current, $comparisons->getMonthAgo(), 'vs 28 days ago');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        $comparison = $this->comparisonService->compare($current, $comparisons->getYearAgo(), 'vs 1 year ago');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        if (count($lines) === 1) {
            return '';
        }

        return implode("\n", $lines);
    }

    public function buildWeeklyComparisons(
        MetricResultInterface $current,
        MetricComparisonsInterface $comparisons
    ): string {
        $lines = ['*Comparisons:*'];

        $comparison =
            $this->comparisonService->compare($current, $comparisons->getWeekAgo(), 'vs week before');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        $comparison =
            $this->comparisonService->compare($current, $comparisons->getMonthAgo(), 'vs 4 weeks ago');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        $comparison =
            $this->comparisonService->compare($current, $comparisons->getYearAgo(), 'vs same week last year');
        if ($comparison !== null) {
            $lines[] = '• ' . $this->comparisonTextFormatter->format($comparison);
        }

        if (count($lines) === 1) {
            return '';
        }

        return implode("\n", $lines);
    }
}
