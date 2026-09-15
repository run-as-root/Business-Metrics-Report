<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email\Formatter;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter\MetricCardDataBuilder;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter\WeeklyFormatter;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WeeklyFormatterTest extends TestCase
{
    private CalculateMetricsComparisonService|MockObject $comparisonService;
    private TimezoneInterface|MockObject $timezone;
    private WeeklyFormatter $sut;

    protected function setUp(): void
    {
        $this->comparisonService = $this->createMock(CalculateMetricsComparisonService::class);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnArgument(0);

        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->timezone->method('getConfigTimezone')->willReturn('UTC');

        $this->sut = new WeeklyFormatter(
            $this->comparisonService,
            new MetricCardDataBuilder(new ComparisonTextFormatter(), $escaper),
            $this->timezone,
        );
    }

    public function testGetReportType(): void
    {
        $this->assertSame('weekly', $this->sut->getReportType());
    }

    public function testSubjectContainsWeekDateRange(): void
    {
        $metricsData = [$this->buildMetricWithDateRange('2026-02-09', '2026-02-15')];

        $report = $this->sut->format($metricsData);

        $this->assertStringContainsString('Weekly Business Metrics Report', $report->getSubject());
        $this->assertStringContainsString('Feb 9 - Feb 15, 2026', $report->getSubject());
    }

    public function testMetricsDataContainsExpectedValues(): void
    {
        $metricsData = [$this->buildMetricWithDateRange('2026-02-09', '2026-02-15')];

        $report = $this->sut->format($metricsData);
        $metrics = $report->getMetrics();

        $this->assertCount(1, $metrics);
        $this->assertSame('Revenue', $metrics[0]['metricLabel']);
        $this->assertSame('€5,678.90', $metrics[0]['currentValue']);
        $this->assertSame('vs week before', $metrics[0]['column1']->getData('label'));
        $this->assertSame('vs 4 weeks ago', $metrics[0]['column2']->getData('label'));
        $this->assertSame('vs same week last year', $metrics[0]['column3']->getData('label'));
    }

    public function testComparisonCellGreenWhenAboveFivePercent(): void
    {
        $comparisonResult = $this->buildComparisonResult(12.5, '€1,100.00');
        $this->comparisonService->method('compare')->willReturn($comparisonResult);

        $metricsData = [$this->buildMetricWithDateRange('2026-02-09', '2026-02-15', withComparisons: true)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('#15803d', $metric['column1']->getData('changeColor'));
        $this->assertStringContainsString('🟢', $metric['column1']->getData('changeText'));
        $this->assertStringContainsString('+12.5%', $metric['column1']->getData('changeText'));
        $this->assertSame('€1,100.00', $metric['column1']->getData('value'));
    }

    public function testComparisonCellIsNaWhenNoComparisonData(): void
    {
        $metricsData = [$this->buildMetricWithDateRange('2026-02-09', '2026-02-15', withComparisons: false)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('N/A', $metric['column1']->getData('value'));
        $this->assertSame('N/A', $metric['column2']->getData('value'));
        $this->assertSame('N/A', $metric['column3']->getData('value'));
    }

    private function buildMetricWithDateRange(
        string $startDate,
        string $endDate,
        bool $withComparisons = false
    ): MetricWithComparisonsInterface {
        $currentMetric = $this->createMock(MetricResultInterface::class);
        $currentMetric->method('getStartDate')
            ->willReturn(new \DateTimeImmutable($startDate, new \DateTimeZone('UTC')));
        $currentMetric->method('getEndDate')
            ->willReturn(new \DateTimeImmutable($endDate, new \DateTimeZone('UTC')));
        $currentMetric->method('getFormattedValue')->willReturn('€5,678.90');

        $comparisons = $this->createMock(MetricComparisonsInterface::class);

        $historicalMetric = $this->createMock(MetricResultInterface::class);
        $historicalMetric->method('hasValue')->willReturn($withComparisons);

        $comparisons->method('getWeekAgo')->willReturn($historicalMetric);
        $comparisons->method('getMonthAgo')->willReturn($historicalMetric);
        $comparisons->method('getYearAgo')->willReturn($historicalMetric);

        $metricData = $this->createMock(MetricWithComparisonsInterface::class);
        $metricData->method('getCurrent')->willReturn($currentMetric);
        $metricData->method('getComparisons')->willReturn($comparisons);
        $metricData->method('getMetricLabel')->willReturn('Revenue');

        return $metricData;
    }

    private function buildComparisonResult(float $percentage, string $historicalValue): ComparisonResult
    {
        return new ComparisonResult($percentage, '', $historicalValue, '');
    }
}
