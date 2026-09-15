<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Service\FluctuationDetector;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FluctuationDetectorTest extends TestCase
{
    private CalculateMetricsComparisonService|MockObject $comparisonService;
    private Config|MockObject $config;

    protected function setUp(): void
    {
        $this->comparisonService = $this->createMock(CalculateMetricsComparisonService::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('getFluctuationDeclineThreshold')->willReturn(-15.0);
    }

    private function makeSut(): FluctuationDetector
    {
        return new FluctuationDetector($this->comparisonService, $this->config);
    }

    private function makeMetricData(
        MetricResultInterface $current,
        MetricResultInterface $weekAgo
    ): MetricWithComparisonsInterface {
        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $comparisons->method('getWeekAgo')->willReturn($weekAgo);

        $metricData = $this->createMock(MetricWithComparisonsInterface::class);
        $metricData->method('getCurrent')->willReturn($current);
        $metricData->method('getComparisons')->willReturn($comparisons);

        return $metricData;
    }

    public function testReturnsFalseWhenNoMetrics(): void
    {
        $this->assertFalse($this->makeSut()->hasFluctuation([]));
    }

    public function testReturnsFalseWhenDeclineBelowThreshold(): void
    {
        $current = $this->createMock(MetricResultInterface::class);
        $weekAgo = $this->createMock(MetricResultInterface::class);
        $metricData = $this->makeMetricData($current, $weekAgo);

        $comparison = new ComparisonResult(-14.9, '100', '118', 'week ago');
        $this->comparisonService->method('compare')->willReturn($comparison);

        $this->assertFalse($this->makeSut()->hasFluctuation([$metricData]));
    }

    public function testReturnsTrueWhenDeclineExceedsThreshold(): void
    {
        $current = $this->createMock(MetricResultInterface::class);
        $weekAgo = $this->createMock(MetricResultInterface::class);
        $metricData = $this->makeMetricData($current, $weekAgo);

        $comparison = new ComparisonResult(-15.1, '85', '100', 'week ago');
        $this->comparisonService->method('compare')->willReturn($comparison);

        $this->assertTrue($this->makeSut()->hasFluctuation([$metricData]));
    }

    public function testReturnsTrueWhenAnyMetricHasFluctuation(): void
    {
        $current = $this->createMock(MetricResultInterface::class);
        $weekAgo = $this->createMock(MetricResultInterface::class);

        $stableMetric = $this->makeMetricData($current, $weekAgo);
        $decliningMetric = $this->makeMetricData($current, $weekAgo);

        $stableComparison = new ComparisonResult(2.0, '102', '100', 'week ago');
        $decliningComparison = new ComparisonResult(-20.0, '80', '100', 'week ago');

        $this->comparisonService->method('compare')
            ->willReturnOnConsecutiveCalls($stableComparison, $decliningComparison);

        $this->assertTrue($this->makeSut()->hasFluctuation([$stableMetric, $decliningMetric]));
    }

    public function testReturnsFalseWhenComparisonIsNull(): void
    {
        $current = $this->createMock(MetricResultInterface::class);
        $weekAgo = $this->createMock(MetricResultInterface::class);
        $metricData = $this->makeMetricData($current, $weekAgo);

        $this->comparisonService->method('compare')->willReturn(null);

        $this->assertFalse($this->makeSut()->hasFluctuation([$metricData]));
    }

    public function testReturnsFalseWhenPercentageChangeIsNull(): void
    {
        $current = $this->createMock(MetricResultInterface::class);
        $weekAgo = $this->createMock(MetricResultInterface::class);
        $metricData = $this->makeMetricData($current, $weekAgo);

        $comparison = new ComparisonResult(null, '100', '0', 'week ago');
        $this->comparisonService->method('compare')->willReturn($comparison);

        $this->assertFalse($this->makeSut()->hasFluctuation([$metricData]));
    }
}
