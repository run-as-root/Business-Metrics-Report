<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email\Formatter;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter\DailyFormatter;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter\MetricCardDataBuilder;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use Magento\Framework\Escaper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DailyFormatterTest extends TestCase
{
    private CalculateMetricsComparisonService|MockObject $comparisonService;
    private TimezoneInterface|MockObject $timezone;
    private DailyFormatter $sut;

    protected function setUp(): void
    {
        $this->comparisonService = $this->createMock(CalculateMetricsComparisonService::class);

        $escaper = $this->createMock(Escaper::class);
        $escaper->method('escapeHtml')->willReturnArgument(0);

        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->timezone->method('getConfigTimezone')->willReturn('UTC');

        $this->sut = new DailyFormatter(
            $this->comparisonService,
            new MetricCardDataBuilder(new ComparisonTextFormatter(), $escaper),
            $this->timezone,
        );
    }

    public function testGetReportType(): void
    {
        $this->assertSame('daily', $this->sut->getReportType());
    }

    public function testSubjectContainsDateLabel(): void
    {
        $metricsData = [$this->buildMetricWithComparisons('2026-02-17')];

        $report = $this->sut->format($metricsData);

        $this->assertStringContainsString('Daily Business Metrics Report', $report->getSubject());
        $this->assertStringContainsString('Tuesday, February 17, 2026', $report->getSubject());
    }

    public function testMetricsDataContainsExpectedValues(): void
    {
        $metricsData = [$this->buildMetricWithComparisons('2026-02-17')];

        $report = $this->sut->format($metricsData);
        $metrics = $report->getMetrics();

        $this->assertCount(1, $metrics);
        $this->assertSame('Revenue', $metrics[0]['metricLabel']);
        $this->assertSame('€1,234.56', $metrics[0]['currentValue']);
        $this->assertSame('vs week ago', $metrics[0]['column1']->getData('label'));
        $this->assertSame('vs 28 days ago', $metrics[0]['column2']->getData('label'));
        $this->assertSame('vs year ago', $metrics[0]['column3']->getData('label'));
    }

    public function testComparisonCellGreenWhenAboveFivePercent(): void
    {
        $comparisonResult = $this->buildComparisonResult(10.0, '€1,000.00');

        $this->comparisonService->method('compare')->willReturn($comparisonResult);

        $metricsData = [$this->buildMetricWithComparisons('2026-02-17', withComparisons: true)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('#15803d', $metric['column1']->getData('changeColor'));
        $this->assertStringContainsString('🟢', $metric['column1']->getData('changeText'));
        $this->assertStringContainsString('+10.0%', $metric['column1']->getData('changeText'));
        $this->assertSame('€1,000.00', $metric['column1']->getData('value'));
    }

    public function testComparisonCellRedWhenBelowMinusFivePercent(): void
    {
        $comparisonResult = $this->buildComparisonResult(-8.0, '€1,200.00');

        $this->comparisonService->method('compare')->willReturn($comparisonResult);

        $metricsData = [$this->buildMetricWithComparisons('2026-02-17', withComparisons: true)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('#dc2626', $metric['column1']->getData('changeColor'));
        $this->assertStringContainsString('🔴', $metric['column1']->getData('changeText'));
        $this->assertStringContainsString('-8.0%', $metric['column1']->getData('changeText'));
        $this->assertSame('€1,200.00', $metric['column1']->getData('value'));
    }

    public function testComparisonCellYellowWhenWithinFivePercent(): void
    {
        $comparisonResult = $this->buildComparisonResult(2.5, '€1,100.00');

        $this->comparisonService->method('compare')->willReturn($comparisonResult);

        $metricsData = [$this->buildMetricWithComparisons('2026-02-17', withComparisons: true)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('#b45309', $metric['column1']->getData('changeColor'));
        $this->assertStringContainsString('🟡', $metric['column1']->getData('changeText'));
        $this->assertStringContainsString('+2.5%', $metric['column1']->getData('changeText'));
    }

    public function testComparisonCellIsNaWhenNoComparisonData(): void
    {
        $metricsData = [$this->buildMetricWithComparisons('2026-02-17', withComparisons: false)];

        $report = $this->sut->format($metricsData);
        $metric = $report->getMetrics()[0];

        $this->assertSame('N/A', $metric['column1']->getData('value'));
        $this->assertSame('N/A', $metric['column2']->getData('value'));
        $this->assertSame('N/A', $metric['column3']->getData('value'));
    }

    private function buildMetricWithComparisons(
        string $date,
        bool $withComparisons = false
    ): MetricWithComparisonsInterface {
        $currentMetric = $this->createMock(MetricResultInterface::class);
        $currentMetric->method('getStartDate')->willReturn(new \DateTimeImmutable($date, new \DateTimeZone('UTC')));
        $currentMetric->method('getFormattedValue')->willReturn('€1,234.56');

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
