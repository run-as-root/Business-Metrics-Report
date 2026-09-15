<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

/**
 * @api
 */
class ComparisonResult
{
    public function __construct(
        private readonly ?float $percentageChange,
        private readonly string $formattedCurrentValue,
        private readonly string $formattedHistoricalValue,
        private readonly string $comparisonPeriod,
    ) {
    }

    public function getPercentageChange(): ?float
    {
        return $this->percentageChange;
    }

    public function getFormattedCurrentValue(): string
    {
        return $this->formattedCurrentValue;
    }

    public function getFormattedHistoricalValue(): string
    {
        return $this->formattedHistoricalValue;
    }

    public function getComparisonPeriod(): string
    {
        return $this->comparisonPeriod;
    }
}
