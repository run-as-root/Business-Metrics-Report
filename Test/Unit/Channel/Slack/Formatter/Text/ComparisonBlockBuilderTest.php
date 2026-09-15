<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Slack\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Slack\Formatter\Text\ComparisonBlockBuilder;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ComparisonBlockBuilderTest extends TestCase
{
    private CalculateMetricsComparisonService|MockObject $comparisonService;
    private ComparisonTextFormatter|MockObject $textFormatter;

    protected function setUp(): void
    {
        $this->comparisonService = $this->createMock(CalculateMetricsComparisonService::class);
        $this->textFormatter = $this->createMock(ComparisonTextFormatter::class);
    }

    public function testBuildDailyComparisonsReturnsEmptyStringWhenNoComparisons(): void
    {
        $comparisons = $this->makeEmptyComparisons();
        $current = $this->createMock(MetricResultInterface::class);

        $sut = new ComparisonBlockBuilder($this->comparisonService, $this->textFormatter);
        $this->assertSame('', $sut->buildDailyComparisons($current, $comparisons));
    }

    public function testBuildDailyComparisonsIncludesHeaderAndBulletPoint(): void
    {
        $this->comparisonService->method('compare')->willReturn(new ComparisonResult(0.0, '', '', ''));
        $this->textFormatter->method('format')->willReturn('formatted comparison');

        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getMonthAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getYearAgo')->willReturn($this->createMock(MetricResultInterface::class));

        $current = $this->createMock(MetricResultInterface::class);

        $sut = new ComparisonBlockBuilder($this->comparisonService, $this->textFormatter);
        $result = $sut->buildDailyComparisons($current, $comparisons);

        $this->assertStringContainsString('*Comparisons:*', $result);
        $this->assertStringContainsString('• formatted comparison', $result);
    }

    public function testBuildDailyComparisonsBuildsLineForEachPresentComparison(): void
    {
        $this->comparisonService->method('compare')->willReturn(new ComparisonResult(0.0, '', '', ''));
        $this->textFormatter->method('format')->willReturnOnConsecutiveCalls('week line', 'month line', 'year line');

        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getMonthAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getYearAgo')->willReturn($this->createMock(MetricResultInterface::class));

        $current = $this->createMock(MetricResultInterface::class);

        $sut = new ComparisonBlockBuilder($this->comparisonService, $this->textFormatter);
        $result = $sut->buildDailyComparisons($current, $comparisons);

        $this->assertStringContainsString('• week line', $result);
        $this->assertStringContainsString('• month line', $result);
        $this->assertStringContainsString('• year line', $result);
    }

    public function testBuildWeeklyComparisonsReturnsEmptyStringWhenNoComparisons(): void
    {
        $comparisons = $this->makeEmptyComparisons();
        $current = $this->createMock(MetricResultInterface::class);

        $sut = new ComparisonBlockBuilder($this->comparisonService, $this->textFormatter);
        $this->assertSame('', $sut->buildWeeklyComparisons($current, $comparisons));
    }

    public function testBuildWeeklyComparisonsIncludesHeaderAndBulletPoint(): void
    {
        $this->comparisonService->method('compare')->willReturn(new ComparisonResult(0.0, '', '', ''));
        $this->textFormatter->method('format')->willReturn('weekly comparison');

        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getMonthAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getYearAgo')->willReturn($this->createMock(MetricResultInterface::class));

        $current = $this->createMock(MetricResultInterface::class);

        $sut = new ComparisonBlockBuilder($this->comparisonService, $this->textFormatter);
        $result = $sut->buildWeeklyComparisons($current, $comparisons);

        $this->assertStringContainsString('*Comparisons:*', $result);
        $this->assertStringContainsString('• weekly comparison', $result);
    }

    private function makeEmptyComparisons(): MetricComparisonsInterface|MockObject
    {
        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getMonthAgo')->willReturn($this->createMock(MetricResultInterface::class));
        $comparisons->method('getYearAgo')->willReturn($this->createMock(MetricResultInterface::class));
        return $comparisons;
    }
}
