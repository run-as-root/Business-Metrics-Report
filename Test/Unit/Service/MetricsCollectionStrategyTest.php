<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricComparisonsInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;
use RunAsRoot\BusinessMetricsReport\Service\MetricsCollectionStrategy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetricsCollectionStrategyTest extends TestCase
{
    private MetricWithComparisonsInterfaceFactory|MockObject $metricWithComparisonsFactory;
    private MetricComparisonsInterfaceFactory|MockObject $metricComparisonsFactory;
    private MetricsCollectionStrategy $sut;

    protected function setUp(): void
    {
        $this->metricWithComparisonsFactory = $this->createMock(MetricWithComparisonsInterfaceFactory::class);
        $this->metricComparisonsFactory = $this->createMock(MetricComparisonsInterfaceFactory::class);
        $this->sut = new MetricsCollectionStrategy(
            $this->metricWithComparisonsFactory,
            $this->metricComparisonsFactory,
        );
    }

    public function testSortCollectorsOrdersByAscendingSortOrder(): void
    {
        $collectorA = $this->createMock(MetricCollectorInterface::class);
        $collectorA->method('getSortOrder')->willReturn(20);

        $collectorB = $this->createMock(MetricCollectorInterface::class);
        $collectorB->method('getSortOrder')->willReturn(10);

        $result = $this->sut->sortCollectors([$collectorA, $collectorB]);

        $this->assertSame($collectorB, $result[0]);
        $this->assertSame($collectorA, $result[1]);
    }

    public function testSortCollectorsWithSingleCollector(): void
    {
        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getSortOrder')->willReturn(10);

        $result = $this->sut->sortCollectors([$collector]);

        $this->assertCount(1, $result);
        $this->assertSame($collector, $result[0]);
    }

    public function testCollectWithComparisonsReturnsMappedMetricData(): void
    {
        $currentResult = $this->createMock(MetricResultInterface::class);
        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $metricWithComparisons = $this->createMock(MetricWithComparisonsInterface::class);
        $metricWithComparisons->method('getMetricCode')->willReturn('revenue');
        $metricWithComparisons->method('getMetricLabel')->willReturn('Revenue');
        $metricWithComparisons->method('getCurrent')->willReturn($currentResult);

        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getMetricCode')->willReturn('revenue');
        $collector->method('getMetricLabel')->willReturn('Revenue');
        $collector->method('collect')->willReturn($currentResult);

        $this->metricComparisonsFactory->method('create')->willReturn($comparisons);
        $this->metricWithComparisonsFactory->method('create')->willReturn($metricWithComparisons);

        $dateRange = $this->makeDateRange();
        $result = $this->sut->collectWithComparisons($collector, $dateRange, $dateRange, $dateRange, $dateRange);

        $this->assertSame('revenue', $result->getMetricCode());
        $this->assertSame('Revenue', $result->getMetricLabel());
        $this->assertSame($currentResult, $result->getCurrent());
    }

    public function testCollectWithComparisonsThrowsWhenHistoricalCollectionFails(): void
    {
        $currentResult = $this->createMock(MetricResultInterface::class);
        $callCount = 0;

        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getMetricCode')->willReturn('revenue');
        $collector->method('getMetricLabel')->willReturn('Revenue');
        $collector->method('collect')->willReturnCallback(
            static function () use (&$callCount, $currentResult): MetricResultInterface {
                $callCount++;
                if ($callCount === 1) {
                    return $currentResult;
                }
                throw new \RuntimeException('Historical collection failed');
            }
        );

        $dateRange = $this->makeDateRange();

        $this->expectException(\RuntimeException::class);
        $this->sut->collectWithComparisons($collector, $dateRange, $dateRange, $dateRange, $dateRange);
    }

    public function testSortCollectorsWithEmptyArray(): void
    {
        $this->assertSame([], $this->sut->sortCollectors([]));
    }

    public function testCollectWithComparisonsPassesCorrectArgsToMetricWithComparisonsFactory(): void
    {
        $currentResult = $this->createMock(MetricResultInterface::class);
        $comparisons = $this->createMock(MetricComparisonsInterface::class);
        $metricWithComparisons = $this->createMock(MetricWithComparisonsInterface::class);

        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getMetricCode')->willReturn('order_count');
        $collector->method('getMetricLabel')->willReturn('Order Count');
        $collector->method('collect')->willReturn($currentResult);

        $this->metricComparisonsFactory->method('create')->willReturn($comparisons);

        $capturedArgs = [];
        $this->metricWithComparisonsFactory
            ->method('create')
            ->willReturnCallback(function (array $args) use (&$capturedArgs, $metricWithComparisons) {
                $capturedArgs = $args;
                return $metricWithComparisons;
            });

        $dateRange = $this->makeDateRange();
        $this->sut->collectWithComparisons($collector, $dateRange, $dateRange, $dateRange, $dateRange);

        $this->assertSame('order_count', $capturedArgs['metricCode']);
        $this->assertSame('Order Count', $capturedArgs['metricLabel']);
        $this->assertSame($currentResult, $capturedArgs['current']);
        $this->assertSame($comparisons, $capturedArgs['comparisons']);
    }

    public function testCollectWithComparisonsPassesCorrectArgsToMetricComparisonsFactory(): void
    {
        $weekAgoResult = $this->createMock(MetricResultInterface::class);
        $monthAgoResult = $this->createMock(MetricResultInterface::class);
        $yearAgoResult = $this->createMock(MetricResultInterface::class);
        $currentResult = $this->createMock(MetricResultInterface::class);
        $metricWithComparisons = $this->createMock(MetricWithComparisonsInterface::class);

        $callCount = 0;
        $results = [$currentResult, $weekAgoResult, $monthAgoResult, $yearAgoResult];
        $collector = $this->createMock(MetricCollectorInterface::class);
        $collector->method('getMetricCode')->willReturn('revenue');
        $collector->method('getMetricLabel')->willReturn('Revenue');
        $collector->method('collect')->willReturnCallback(
            static function () use (&$callCount, $results): MetricResultInterface {
                return $results[$callCount++];
            }
        );

        $capturedArgs = [];
        $this->metricComparisonsFactory
            ->method('create')
            ->willReturnCallback(function (array $args) use (&$capturedArgs) {
                $capturedArgs = $args;
                return $this->createMock(MetricComparisonsInterface::class);
            });
        $this->metricWithComparisonsFactory->method('create')->willReturn($metricWithComparisons);

        $current = $this->makeDateRange();
        $weekAgo = new DateRange(new \DateTimeImmutable('2024-12-25'), new \DateTimeImmutable('2024-12-25'));
        $monthAgo = new DateRange(new \DateTimeImmutable('2024-12-04'), new \DateTimeImmutable('2024-12-04'));
        $yearAgo = new DateRange(new \DateTimeImmutable('2024-01-01'), new \DateTimeImmutable('2024-01-01'));

        $this->sut->collectWithComparisons($collector, $current, $weekAgo, $monthAgo, $yearAgo);

        $this->assertSame($weekAgoResult, $capturedArgs['weekAgo']);
        $this->assertSame($monthAgoResult, $capturedArgs['monthAgo']);
        $this->assertSame($yearAgoResult, $capturedArgs['yearAgo']);
    }

    private function makeDateRange(): DateRange
    {
        return new DateRange(
            startDate: new \DateTimeImmutable('2025-01-01 00:00:00'),
            endDate: new \DateTimeImmutable('2025-01-01 23:59:59'),
        );
    }
}
