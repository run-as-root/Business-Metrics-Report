<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use Magento\Framework\DataObject;
use Magento\Framework\Escaper;

class MetricCardDataBuilder
{
    private const NA_TEXT = 'N/A';
    private const NA_CHANGE_TEXT = '—';
    private const NA_COLOR = '#cbd5e1';

    public function __construct(
        private readonly ComparisonTextFormatter $comparisonTextFormatter,
        private readonly Escaper $escaper,
    ) {
    }

    /**
     * @return array{
     *     metricLabel: string,
     *     currentValue: string,
     *     hero: DataObject,
     *     column1: DataObject,
     *     column2: DataObject,
     *     column3: DataObject,
     * }
     */
    public function buildMetricCardData(
        MetricWithComparisonsInterface $metricData,
        ?ComparisonResult $primaryComparison,
        ?ComparisonResult $secondaryComparison,
        ?ComparisonResult $tertiaryComparison,
        string $primaryLabel,
        string $secondaryLabel,
        string $tertiaryLabel,
    ): array {
        return [
            'metricLabel' => $this->escapeString($metricData->getMetricLabel()),
            'currentValue' => $this->escapeString($metricData->getCurrent()->getFormattedValue()),
            'hero' => $this->buildHeroData($primaryComparison, $primaryLabel),
            'column1' => $this->buildColumnData($primaryLabel, $primaryComparison),
            'column2' => $this->buildColumnData($secondaryLabel, $secondaryComparison),
            'column3' => $this->buildColumnData($tertiaryLabel, $tertiaryComparison),
        ];
    }

    private function buildHeroData(?ComparisonResult $primaryComparison, string $primaryLabel): DataObject
    {
        if ($primaryComparison === null) {
            return new DataObject(['text' => '', 'color' => '', 'label' => '']);
        }

        $percentageChange = $primaryComparison->getPercentageChange();
        $indicator = $this->comparisonTextFormatter->getVisualIndicator($percentageChange);
        $changeText = $this->formatChangeText($percentageChange);

        return new DataObject([
            'text' => $this->escapeString($indicator . ' ' . $changeText),
            'color' => $this->resolveChangeColor($percentageChange),
            'label' => $this->escapeString($primaryLabel),
        ]);
    }

    private function buildColumnData(string $label, ?ComparisonResult $comparison): DataObject
    {
        if ($comparison === null) {
            return new DataObject([
                'label' => $this->escapeString($label),
                'value' => self::NA_TEXT,
                'changeText' => self::NA_CHANGE_TEXT,
                'changeColor' => self::NA_COLOR,
            ]);
        }

        $percentageChange = $comparison->getPercentageChange();
        $indicator = $this->comparisonTextFormatter->getVisualIndicator($percentageChange);
        $changeText = $percentageChange !== null
            ? $indicator . ' ' . ($percentageChange >= 0 ? '+' : '') . number_format($percentageChange, 1) . '%'
            : $indicator . ' —';

        return new DataObject([
            'label' => $this->escapeString($label),
            'value' => $this->escapeString($comparison->getFormattedHistoricalValue()),
            'changeText' => $this->escapeString($changeText),
            'changeColor' => $this->resolveChangeColor($percentageChange),
        ]);
    }

    private function formatChangeText(?float $percentageChange): string
    {
        return $percentageChange !== null
            ? ($percentageChange >= 0 ? '+' : '') . number_format($percentageChange, 1) . '%'
            : '—';
    }

    private function resolveChangeColor(?float $percentageChange): string
    {
        if ($percentageChange === null || $percentageChange === 0.0) {
            return '#64748b';
        }

        if ($percentageChange > 5.0) {
            return '#15803d';
        }

        if ($percentageChange < -5.0) {
            return '#dc2626';
        }

        return '#b45309';
    }

    private function escapeString(string $value): string
    {
        $escaped = $this->escaper->escapeHtml($value);

        return is_string($escaped) ? $escaped : implode(' ', $escaped);
    }
}
