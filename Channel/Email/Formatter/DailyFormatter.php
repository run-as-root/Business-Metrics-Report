<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter;

use DateTimezone;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\EmailFormattedReport;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailFormattedReportInterface;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Api\ReportType;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DailyFormatter implements EmailReportFormatterInterface
{
    public function __construct(
        private readonly CalculateMetricsComparisonService $comparisonService,
        private readonly MetricCardDataBuilder $metricCardDataBuilder,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getReportType(): string
    {
        return ReportType::DAILY;
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function format(array $metricsData): EmailFormattedReportInterface
    {
        $dateLabel = $this->resolveDateLabel($metricsData);

        return new EmailFormattedReport(
            '📊 Daily Business Metrics Report — ' . $dateLabel,
            'Daily Report',
            'Yesterday — ' . $dateLabel,
            $this->buildMetrics($metricsData),
        );
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    private function resolveDateLabel(array $metricsData): string
    {
        $firstMetric = reset($metricsData);
        if (!$firstMetric) {
            return '';
        }

        return $firstMetric->getCurrent()
            ->getStartDate()
            ->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()))
            ->format('l, F j, Y');
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @return array<int, array<string, mixed>>
     */
    private function buildMetrics(array $metricsData): array
    {
        $metrics = [];

        foreach ($metricsData as $metricData) {
            $current = $metricData->getCurrent();
            $comparisons = $metricData->getComparisons();

            $metrics[] = $this->metricCardDataBuilder->buildMetricCardData(
                $metricData,
                $this->comparisonService->compare($current, $comparisons->getWeekAgo(), 'vs week ago'),
                $this->comparisonService->compare($current, $comparisons->getMonthAgo(), 'vs 28 days ago'),
                $this->comparisonService->compare($current, $comparisons->getYearAgo(), 'vs year ago'),
                'vs week ago',
                'vs 28 days ago',
                'vs year ago',
            );
        }

        return $metrics;
    }
}
