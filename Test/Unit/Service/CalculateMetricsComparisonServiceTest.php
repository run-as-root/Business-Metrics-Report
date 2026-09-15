<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Service\CalculateMetricsComparisonService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalculateMetricsComparisonServiceTest extends TestCase
{
    public function testCompareCalculatesPositivePercentageChange(): void
    {
        $current = $this->makeMetricResult(150.0, '$150.00');
        $historical = $this->makeMetricResult(100.0, '$100.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertEqualsWithDelta(50.0, $result->getPercentageChange(), 0.001);
    }

    public function testCompareCalculatesNegativePercentageChange(): void
    {
        $current = $this->makeMetricResult(80.0, '$80.00');
        $historical = $this->makeMetricResult(100.0, '$100.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertEqualsWithDelta(-20.0, $result->getPercentageChange(), 0.001);
    }

    public function testCompareReturnsNullPercentageWhenHistoricalIsZeroAndCurrentIsNonZero(): void
    {
        $current = $this->makeMetricResult(100.0, '$100.00');
        $historical = $this->makeMetricResult(0.0, '$0.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertNotNull($result);
        $this->assertNull($result->getPercentageChange());
    }

    public function testCompareReturnsZeroPercentageWhenBothAreZero(): void
    {
        $current = $this->makeMetricResult(0.0, '$0.00');
        $historical = $this->makeMetricResult(0.0, '$0.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(0.0, $result->getPercentageChange(), 0.001);
    }

    public function testCompareReturnsNullWhenHistoricalHasNoValue(): void
    {
        $current = $this->makeMetricResult(100.0, '$100.00');
        $historical = $this->createMock(MetricResultInterface::class);
        $historical->method('hasValue')->willReturn(false);

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertNull($result);
    }

    public function testCompareSetsFormattedValuesAndPeriod(): void
    {
        $current = $this->makeMetricResult(150.0, '$150.00');
        $historical = $this->makeMetricResult(100.0, '$100.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 month ago');

        $this->assertSame('$150.00', $result->getFormattedCurrentValue());
        $this->assertSame('$100.00', $result->getFormattedHistoricalValue());
        $this->assertSame('vs 1 month ago', $result->getComparisonPeriod());
    }

    public function testCompareCalculatesZeroPercentageWhenValuesAreEqual(): void
    {
        $current = $this->makeMetricResult(100.0, '$100.00');
        $historical = $this->makeMetricResult(100.0, '$100.00');

        $sut = new CalculateMetricsComparisonService();
        $result = $sut->compare($current, $historical, 'vs 1 week ago');

        $this->assertEqualsWithDelta(0.0, $result->getPercentageChange(), 0.001);
    }

    private function makeMetricResult(float $value, string $formattedValue): MetricResultInterface|MockObject
    {
        $metric = $this->createMock(MetricResultInterface::class);
        $metric->method('getValue')->willReturn($value);
        $metric->method('getFormattedValue')->willReturn($formattedValue);
        $metric->method('hasValue')->willReturn(true);
        return $metric;
    }
}
