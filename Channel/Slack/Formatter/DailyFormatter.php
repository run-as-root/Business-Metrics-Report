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

class DailyFormatter implements SlackReportFormatterInterface
{
    public function __construct(
        private readonly ComparisonBlockBuilder $comparisonBlockBuilder,
        private readonly SummaryTextFormatter $summaryTextFormatter,
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getReportType(): string
    {
        return ReportType::DAILY;
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
                        "*Yesterday's Performance*\n_%s_",
                        $currentMetric
                            ->getStartDate()
                            ->setTimezone(new DateTimezone($this->timezone->getConfigTimezone()))
                            ->format('l, F j, Y')
                    )
                ]
            ];
        }

        $blocks[] = ['type' => 'divider'];

        foreach ($metricsData as $metricData) {
            $yesterdayMetric = $metricData->getCurrent();
            $comparisons = $metricData->getComparisons();

            $blocks[] = [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => sprintf(
                            "*%s Yesterday:*\n%s",
                            $metricData->getMetricLabel(),
                            $yesterdayMetric->getFormattedValue()
                        )
                    ]
                ]
            ];

            $comparisonText = $this->comparisonBlockBuilder->buildDailyComparisons(
                $yesterdayMetric,
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
}
