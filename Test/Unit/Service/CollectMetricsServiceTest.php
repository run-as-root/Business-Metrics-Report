<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;
use RunAsRoot\BusinessMetricsReport\Service\CollectMetricsService;
use RunAsRoot\BusinessMetricsReport\Api\MetricsCollectionStrategyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CollectMetricsServiceTest extends TestCase
{
    private MetricsCollectionStrategyInterface|MockObject $strategy;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(MetricsCollectionStrategyInterface::class);
    }

    public function testCollectAllMetricsReturnsEmptyArrayForNoCollectors(): void
    {
        $this->strategy->method('sortCollectors')->willReturn([]);

        $sut = new CollectMetricsService([], $this->strategy);
        $result = $sut->collectAllMetrics($this->makeDateRangeSet());

        $this->assertSame([], $result);
    }

    public function testCollectAllMetricsReturnsResultForEachCollector(): void
    {
        $metricWithComparisons = $this->createMock(MetricWithComparisonsInterface::class);
        $collector = $this->createMock(MetricCollectorInterface::class);

        $this->strategy->method('sortCollectors')->willReturn([$collector]);
        $this->strategy->method('collectWithComparisons')->willReturn($metricWithComparisons);

        $sut = new CollectMetricsService([$collector], $this->strategy);
        $result = $sut->collectAllMetrics($this->makeDateRangeSet());

        $this->assertCount(1, $result);
        $this->assertSame($metricWithComparisons, $result[0]);
    }

    public function testCollectAllMetricsThrowsWhenCollectorFails(): void
    {
        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getMetricCode')->willReturn('revenue');

        $this->strategy->method('sortCollectors')->willReturn([$collector]);
        $this->strategy->method('collectWithComparisons')->willThrowException(
            new \RuntimeException('DB connection failed')
        );

        $sut = new CollectMetricsService([$collector], $this->strategy);

        $this->expectException(\RuntimeException::class);
        $sut->collectAllMetrics($this->makeDateRangeSet());
    }

    public function testCollectAllMetricsPassesDateRangesToStrategy(): void
    {
        $collector = $this->createMock(MetricCollectorInterface::class);
        $metricResult = $this->createMock(MetricWithComparisonsInterface::class);
        $dateRangeSet = $this->makeDateRangeSet();

        $this->strategy->method('sortCollectors')->willReturn([$collector]);
        $this->strategy->expects($this->once())
            ->method('collectWithComparisons')
            ->with(
                $collector,
                $dateRangeSet->currentRange,
                $dateRangeSet->weekAgoRange,
                $dateRangeSet->monthAgoRange,
                $dateRangeSet->yearAgoRange,
            )
            ->willReturn($metricResult);

        $sut = new CollectMetricsService([$collector], $this->strategy);
        $sut->collectAllMetrics($dateRangeSet);
    }

    private function makeDateRangeSet(): DateRangeSet
    {
        $range = new DateRange(
            startDate: new \DateTimeImmutable('2025-01-01 00:00:00'),
            endDate: new \DateTimeImmutable('2025-01-01 23:59:59'),
        );
        return new DateRangeSet($range, $range, $range, $range);
    }
}
