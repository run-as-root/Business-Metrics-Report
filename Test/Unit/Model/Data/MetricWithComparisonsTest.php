<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Data;

use RunAsRoot\BusinessMetricsReport\Model\Data\MetricComparisons;
use RunAsRoot\BusinessMetricsReport\Model\Data\MetricResult;
use RunAsRoot\BusinessMetricsReport\Model\Data\MetricWithComparisons;
use PHPUnit\Framework\TestCase;

class MetricWithComparisonsTest extends TestCase
{
    public function testGetters(): void
    {
        $current = new MetricResult(
            'revenue',
            1500.0,
            '$1,500.00',
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            new \DateTimeImmutable('2025-01-01 23:59:59')
        );
        $noValue = new MetricResult(
            'revenue',
            null,
            '',
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            new \DateTimeImmutable('2025-01-01 23:59:59')
        );
        $comparisons = new MetricComparisons($noValue, $noValue, $noValue);

        $sut = new MetricWithComparisons('revenue', 'Revenue', $current, $comparisons);

        $this->assertSame('revenue', $sut->getMetricCode());
        $this->assertSame('Revenue', $sut->getMetricLabel());
        $this->assertSame($current, $sut->getCurrent());
        $this->assertSame($comparisons, $sut->getComparisons());
    }
}
