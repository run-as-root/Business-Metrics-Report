<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Data;

use RunAsRoot\BusinessMetricsReport\Model\Data\MetricComparisons;
use RunAsRoot\BusinessMetricsReport\Model\Data\MetricResult;
use PHPUnit\Framework\TestCase;

class MetricComparisonsTest extends TestCase
{
    public function testGetters(): void
    {
        $weekAgo = $this->makeMetricResult(100.0, '$100.00');
        $monthAgo = $this->makeMetricResult(90.0, '$90.00');
        $yearAgo = $this->makeMetricResult(80.0, '$80.00');

        $sut = new MetricComparisons($weekAgo, $monthAgo, $yearAgo);

        $this->assertSame($weekAgo, $sut->getWeekAgo());
        $this->assertSame($monthAgo, $sut->getMonthAgo());
        $this->assertSame($yearAgo, $sut->getYearAgo());
        $this->assertTrue($sut->hasAnyComparisons());
    }

    public function testHasAnyComparisonsFalseWhenAllHaveNoValue(): void
    {
        $sut = new MetricComparisons(
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResultWithNoValue()
        );

        $this->assertFalse($sut->hasAnyComparisons());
    }

    public function testHasAnyComparisonsTrueWhenOnlyWeekAgoHasValue(): void
    {
        $sut = new MetricComparisons(
            $this->makeMetricResult(100.0, '$100.00'),
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResultWithNoValue()
        );

        $this->assertTrue($sut->hasAnyComparisons());
    }

    public function testHasAnyComparisonsTrueWhenOnlyMonthAgoHasValue(): void
    {
        $sut = new MetricComparisons(
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResult(90.0, '$90.00'),
            $this->makeMetricResultWithNoValue()
        );

        $this->assertTrue($sut->hasAnyComparisons());
    }

    public function testHasAnyComparisonsTrueWhenOnlyYearAgoHasValue(): void
    {
        $sut = new MetricComparisons(
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResultWithNoValue(),
            $this->makeMetricResult(80.0, '$80.00')
        );

        $this->assertTrue($sut->hasAnyComparisons());
    }

    private function makeMetricResult(float $value, string $formattedValue): MetricResult
    {
        return new MetricResult(
            'revenue',
            $value,
            $formattedValue,
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            new \DateTimeImmutable('2025-01-01 23:59:59')
        );
    }

    private function makeMetricResultWithNoValue(): MetricResult
    {
        return new MetricResult(
            'revenue',
            null,
            '',
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            new \DateTimeImmutable('2025-01-01 23:59:59')
        );
    }
}
