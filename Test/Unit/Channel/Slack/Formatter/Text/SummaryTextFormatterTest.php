<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Slack\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text\SummaryTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SummaryTextFormatterTest extends TestCase
{
    private CalculateMetricsComparisonService|MockObject $comparisonService;

    protected function setUp(): void
    {
        $this->comparisonService = $this->createMock(CalculateMetricsComparisonService::class);
    }

    public function testFormatSummaryReturnsEmptyStringForNoMetrics(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $this->assertSame('', $sut->formatSummary([]));
    }

    public function testFormatSummarySkipsMetricWhenCompareReturnsNull(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $this->assertSame('', $sut->formatSummary([$this->makeMetricWithPercentage('Revenue', null)]));
    }

    public function testFormatSummarySkipsMetricWhenPercentageChangeIsNull(): void
    {
        $this->comparisonService->method('compare')->willReturn(new ComparisonResult(null, '', '', ''));
        $sut = new SummaryTextFormatter($this->comparisonService);
        $this->assertSame('', $sut->formatSummary([$this->makeMetricStructure('Revenue')]));
    }

    public function testFormatSummaryShowsStrongGrowth(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $result = $sut->formatSummary([$this->makeMetricWithPercentage('Revenue', 10.0)]);
        $this->assertSame('Revenue: Strong growth', $result);
    }

    public function testFormatSummaryShowsSlightIncrease(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $result = $sut->formatSummary([$this->makeMetricWithPercentage('Revenue', 3.0)]);
        $this->assertSame('Revenue: Slight increase', $result);
    }

    public function testFormatSummaryShowsSlightDecrease(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $result = $sut->formatSummary([$this->makeMetricWithPercentage('Revenue', -3.0)]);
        $this->assertSame('Revenue: Slight decrease', $result);
    }

    public function testFormatSummaryShowsNotableDecline(): void
    {
        $sut = new SummaryTextFormatter($this->comparisonService);
        $result = $sut->formatSummary([$this->makeMetricWithPercentage('Revenue', -10.0)]);
        $this->assertSame('Revenue: Notable decline', $result);
    }

    public function testFormatSummaryJoinsMultipleMetricsWithPipe(): void
    {
        $this->comparisonService->method('compare')->willReturnOnConsecutiveCalls(
            new ComparisonResult(10.0, '', '', ''),
            new ComparisonResult(-10.0, '', '', '')
        );

        $sut = new SummaryTextFormatter($this->comparisonService);
        $result = $sut->formatSummary([
            $this->makeMetricStructure('Revenue'),
            $this->makeMetricStructure('Orders'),
        ]);

        $this->assertSame('Revenue: Strong growth | Orders: Notable decline', $result);
    }

    private function makeMetricWithPercentage(string $label, ?float $percentage): MetricWithComparisonsInterface
    {
        $comparisonResult = $percentage !== null ? new ComparisonResult($percentage, '', '', '') : null;
        $this->comparisonService->method('compare')->willReturn($comparisonResult);
        return $this->makeMetricStructure($label);
    }

    private function makeMetricStructure(string $label): MetricWithComparisonsInterface
    {
        $weekAgo = $this->createMock(MetricResultInterface::class);
        $current = $this->createMock(MetricResultInterface::class);
        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($weekAgo);

        $metric = $this->createMock(MetricWithComparisonsInterface::class);
        $metric->method('getMetricLabel')->willReturn($label);
        $metric->method('getCurrent')->willReturn($current);
        $metric->method('getComparisons')->willReturn($comparisons);
        return $metric;
    }
}
