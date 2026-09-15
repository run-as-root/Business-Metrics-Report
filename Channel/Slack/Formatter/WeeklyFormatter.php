<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter;

use DateTimezone;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text\ComparisonBlockBuilder;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text\SummaryTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Slack\SlackReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Api\ReportType;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class WeeklyFormatter implements SlackReportFormatterInterface
{
    public function __construct(
        private readonly ComparisonBlockBuilder $comparisonBlockBuilder,
        private readonly SummaryTextFormatter $summaryTextFormatter,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getReportType(): string
    {
        return ReportType::WEEKLY;
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @return array<string, mixed>
     */
    public function format(array $metricsData): array
    {
        $blocks = [];

        $firstMetric = reset($metricsData);
        if ($firstMetric) {
            $currentMetric = $firstMetric->getCurrent();
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        "*Previous Week Performance*\n_%s - %s_",
                        $currentMetric->getStartDate()
                            ->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()))
                            ->format('M j'),
                        $currentMetric->getEndDate()
                            ->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()))
                            ->format('M j, Y')
                    )
                ]
            ];
        }

        $blocks[] = ['type' => 'divider'];

        foreach ($metricsData as $metricData) {
            foreach ($this->buildMetricBlocks($metricData) as $block) {
                $blocks[] = $block;
            }
        }

        $summary = $this->summaryTextFormatter->formatSummary($metricsData);
        if ($summary) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => '*Summary:* ' . $summary
                ]
            ];
        }

        return ['blocks' => $blocks];
    }

    /**
     * @return array<int, mixed>
     */
    private function buildMetricBlocks(MetricWithComparisonsInterface $metricData): array
    {
        $weekMetric = $metricData->getCurrent();
        $comparisons = $metricData->getComparisons();

        $blocks = [];

        $blocks[] = [
            'type' => 'section',
            'fields' => [
                [
                    'type' => 'mrkdwn',
                    'text' => sprintf(
                        "*%s (Week):*\n%s",
                        $metricData->getMetricLabel(),
                        $weekMetric->getFormattedValue()
                    )
                ]
            ]
        ];

        $comparisonText = $this->comparisonBlockBuilder->buildWeeklyComparisons(
            $weekMetric,
            $comparisons
        );

        if ($comparisonText) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => $comparisonText
                ]
            ];
        }

        $blocks[] = ['type' => 'divider'];

        return $blocks;
    }
}
