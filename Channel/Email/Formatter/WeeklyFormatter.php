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

class WeeklyFormatter implements EmailReportFormatterInterface
{
    public function __construct(
        private readonly CalculateMetricsComparisonService $comparisonService,
        private readonly MetricCardDataBuilder $metricCardDataBuilder,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getReportType(): string
    {
        return ReportType::WEEKLY;
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function format(array $metricsData): EmailFormattedReportInterface
    {
        $dateRangeLabel = $this->resolveDateRangeLabel($metricsData);

        return new EmailFormattedReport(
            '📊 Weekly Business Metrics Report — ' . $dateRangeLabel,
            'Weekly Report',
            'Previous Week — ' . $dateRangeLabel,
            $this->buildMetrics($metricsData),
        );
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    private function resolveDateRangeLabel(array $metricsData): string
    {
        $firstMetric = reset($metricsData);
        if (!$firstMetric) {
            return '';
        }

        $current = $firstMetric->getCurrent();

        $startDate = $current->getStartDate()->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()));
        $endDate = $current->getEndDate()->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()));

        return $startDate->format('M j') . ' - ' . $endDate->format('M j, Y');
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
                $this->comparisonService->compare($current, $comparisons->getWeekAgo(), 'vs week before'),
                $this->comparisonService->compare($current, $comparisons->getMonthAgo(), 'vs 4 weeks ago'),
                $this->comparisonService->compare($current, $comparisons->getYearAgo(), 'vs same week last year'),
                'vs week before',
                'vs 4 weeks ago',
                'vs same week last year',
            );
        }

        return $metrics;
    }
}
