<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Formatter\Text;

use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use PHPUnit\Framework\TestCase;

class ComparisonTextFormatterTest extends TestCase
{
    private ComparisonTextFormatter $sut;

    protected function setUp(): void
    {
        $this->sut = new ComparisonTextFormatter();
    }

    public function testFormatUsesGreenIndicatorAboveFivePercent(): void
    {
        $comparison = new ComparisonResult(10.0, '$110.00', '$100.00', 'vs 1 week ago');
        $this->assertStringContainsString('🟢', $this->sut->format($comparison));
    }

    public function testFormatUsesRedIndicatorBelowMinusFivePercent(): void
    {
        $comparison = new ComparisonResult(-10.0, '$110.00', '$120.00', 'vs 1 week ago');
        $this->assertStringContainsString('🔴', $this->sut->format($comparison));
    }

    public function testFormatUsesYellowIndicatorWithinFivePercent(): void
    {
        $comparison = new ComparisonResult(3.0, '$103.00', '$100.00', 'vs 1 week ago');
        $this->assertStringContainsString('🟡', $this->sut->format($comparison));
    }

    public function testFormatUsesYellowIndicatorAtExactlyFivePercent(): void
    {
        $comparison = new ComparisonResult(5.0, '$105.00', '$100.00', 'vs 1 week ago');
        $this->assertStringContainsString('🟡', $this->sut->format($comparison));
    }

    public function testFormatUsesNeutralIndicatorForZeroPercentChange(): void
    {
        $comparison = new ComparisonResult(0.0, '$100.00', '$100.00', 'vs 1 week ago');
        $this->assertStringContainsString('⚪', $this->sut->format($comparison));
    }

    public function testFormatUsesNeutralIndicatorWhenPercentageIsNull(): void
    {
        $comparison = new ComparisonResult(null, '$100.00', '$0.00', 'vs 1 week ago');
        $result = $this->sut->format($comparison);
        $this->assertStringContainsString('⚪', $result);
        $this->assertStringContainsString('—', $result);
    }

    public function testFormatIncludesPlusSignForPositiveChange(): void
    {
        $comparison = new ComparisonResult(10.0, '$110.00', '$100.00', 'vs 1 week ago');
        $this->assertStringContainsString('+10.0%', $this->sut->format($comparison));
    }

    public function testFormatDoesNotIncludePlusSignForNegativeChange(): void
    {
        $comparison = new ComparisonResult(-8.0, '$92.00', '$100.00', 'vs 1 week ago');
        $result = $this->sut->format($comparison);
        $this->assertStringContainsString('-8.0%', $result);
        $this->assertStringNotContainsString('+-', $result);
    }

    public function testFormatIncludesHistoricalValue(): void
    {
        $comparison = new ComparisonResult(10.0, '$1,099.99', '$999.99', 'vs 1 week ago');
        $this->assertStringContainsString('was $999.99', $this->sut->format($comparison));
    }

    public function testFormatIncludesComparisonPeriod(): void
    {
        $comparison = new ComparisonResult(10.0, '$110.00', '$100.00', 'vs 1 month ago');
        $this->assertStringContainsString('vs 1 month ago', $this->sut->format($comparison));
    }
}
