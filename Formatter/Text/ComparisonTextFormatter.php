<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;

class ComparisonTextFormatter
{
    public function format(ComparisonResult $comparison): string
    {
        $percentageChange = $comparison->getPercentageChange();
        $indicator = $this->getVisualIndicator($percentageChange);

        if ($percentageChange === null) {
            return sprintf(
                '%s: %s — (was %s)',
                $comparison->getComparisonPeriod(),
                $indicator,
                $comparison->getFormattedHistoricalValue()
            );
        }

        $sign = $percentageChange >= 0 ? '+' : '';

        return sprintf(
            '%s: %s %s%s%% (was %s)',
            $comparison->getComparisonPeriod(),
            $indicator,
            $sign,
            number_format($percentageChange, 1),
            $comparison->getFormattedHistoricalValue()
        );
    }

    public function getVisualIndicator(?float $percentageChange): string
    {
        if ($percentageChange === null || $percentageChange === 0.0) {
            return '⚪';
        }

        if ($percentageChange > 5.0) {
            return '🟢';
        }

        if ($percentageChange < -5.0) {
            return '🔴';
        }

        return '🟡';
    }
}
