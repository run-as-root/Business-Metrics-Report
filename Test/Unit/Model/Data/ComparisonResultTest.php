<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Data;

use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use PHPUnit\Framework\TestCase;

class ComparisonResultTest extends TestCase
{
    public function testGetters(): void
    {
        $sut = new ComparisonResult(12.5, '$150.00', '$100.00', 'vs 1 week ago');

        $this->assertSame(12.5, $sut->getPercentageChange());
        $this->assertSame('$150.00', $sut->getFormattedCurrentValue());
        $this->assertSame('$100.00', $sut->getFormattedHistoricalValue());
        $this->assertSame('vs 1 week ago', $sut->getComparisonPeriod());
    }

    public function testNullPercentageChange(): void
    {
        $sut = new ComparisonResult(null, '$150.00', '$0.00', 'vs 1 week ago');

        $this->assertNull($sut->getPercentageChange());
    }

    public function testNegativePercentageChange(): void
    {
        $sut = new ComparisonResult(-25.0, '$75.00', '$100.00', 'vs 1 month ago');

        $this->assertSame(-25.0, $sut->getPercentageChange());
        $this->assertSame('vs 1 month ago', $sut->getComparisonPeriod());
    }
}
