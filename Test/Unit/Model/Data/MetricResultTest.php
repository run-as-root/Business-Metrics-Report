<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Data;

use RunAsRoot\BusinessMetricsReport\Model\Data\MetricResult;
use PHPUnit\Framework\TestCase;

class MetricResultTest extends TestCase
{
    public function testGetters(): void
    {
        $start = new \DateTimeImmutable('2025-01-01 00:00:00');
        $end = new \DateTimeImmutable('2025-01-01 23:59:59');

        $sut = new MetricResult('revenue', 1500.50, '$1,500.50', $start, $end);

        $this->assertSame('revenue', $sut->getMetricCode());
        $this->assertSame(1500.50, $sut->getValue());
        $this->assertSame('$1,500.50', $sut->getFormattedValue());
        $this->assertSame($start, $sut->getStartDate());
        $this->assertSame($end, $sut->getEndDate());
    }

    public function testGetFormattedValueReturnsNaWhenValueIsNull(): void
    {
        $sut = new MetricResult('revenue', null, '', new \DateTimeImmutable(), new \DateTimeImmutable());

        $this->assertSame('N/A', $sut->getFormattedValue());
    }
}
