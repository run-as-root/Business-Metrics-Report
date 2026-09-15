<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email\Formatter;

use RunAsRoot\BusinessMetricsReport\Api\Data\ComparisonResult;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Channel\Email\Formatter\MetricCardDataBuilder;
use RunAsRoot\BusinessMetricsReport\Formatter\Text\ComparisonTextFormatter;
use Magento\Framework\Escaper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetricCardDataBuilderTest extends TestCase
{
    private Escaper|MockObject $escaper;
    private MetricCardDataBuilder $sut;

    protected function setUp(): void
    {
        $this->escaper = $this->createMock(Escaper::class);
        $this->escaper->method('escapeHtml')->willReturnArgument(0);

        $this->sut = new MetricCardDataBuilder(new ComparisonTextFormatter(), $this->escaper);
    }

    public function testReturnsMetricLabelAndCurrentValue(): void
    {
        $metricData = $this->buildMetricWithComparisons('Revenue', '€1,234.56');

        $result = $this->sut->buildMetricCardData(
            $metricData,
            null,
            null,
            null,
            'vs week ago',
            'vs 28 days ago',
            'vs year ago',
        );

        $this->assertSame('Revenue', $result['metricLabel']);
        $this->assertSame('€1,234.56', $result['currentValue']);
    }

    public function testNullComparisonsProduceNaColumnsAndEmptyHero(): void
    {
        $metricData = $this->buildMetricWithComparisons('Revenue', '€1,234.56');

        $result = $this->sut->buildMetricCardData(
            $metricData,
            null,
            null,
            null,
            'vs week ago',
            'vs 28 days ago',
            'vs year ago',
        );

        $this->assertSame('', $result['hero']->getData('text'));
        $this->assertSame('', $result['hero']->getData('color'));
        $this->assertSame('', $result['hero']->getData('label'));

        foreach (['column1', 'column2', 'column3'] as $column) {
            $this->assertSame('N/A', $result[$column]->getData('value'));
            $this->assertSame('—', $result[$column]->getData('changeText'));
            $this->assertSame('#cbd5e1', $result[$column]->getData('changeColor'));
        }

        $this->assertSame('vs week ago', $result['column1']->getData('label'));
        $this->assertSame('vs 28 days ago', $result['column2']->getData('label'));
        $this->assertSame('vs year ago', $result['column3']->getData('label'));
    }

    public function testPositiveComparisonProducesGreenHeroAndColumn(): void
    {
        $metricData = $this->buildMetricWithComparisons('Revenue', '€1,234.56');
        $comparison = new ComparisonResult(10.0, '€1,234.56', '€1,000.00', 'vs week ago');

        $result = $this->sut->buildMetricCardData(
            $metricData,
            $comparison,
            null,
            null,
            'vs week ago',
            'vs 28 days ago',
            'vs year ago',
        );

        $this->assertSame('#15803d', $result['hero']->getData('color'));
        $this->assertStringContainsString('🟢', $result['hero']->getData('text'));
        $this->assertStringContainsString('+10.0%', $result['hero']->getData('text'));

        $this->assertSame('€1,000.00', $result['column1']->getData('value'));
        $this->assertSame('#15803d', $result['column1']->getData('changeColor'));
        $this->assertStringContainsString('+10.0%', $result['column1']->getData('changeText'));
    }

    public function testNegativeComparisonProducesRedColumn(): void
    {
        $metricData = $this->buildMetricWithComparisons('Revenue', '€1,234.56');
        $comparison = new ComparisonResult(-8.0, '€1,234.56', '€1,200.00', 'vs week ago');

        $result = $this->sut->buildMetricCardData(
            $metricData,
            $comparison,
            null,
            null,
            'vs week ago',
            'vs 28 days ago',
            'vs year ago',
        );

        $this->assertSame('#dc2626', $result['column1']->getData('changeColor'));
        $this->assertStringContainsString('🔴', $result['column1']->getData('changeText'));
        $this->assertStringContainsString('-8.0%', $result['column1']->getData('changeText'));
    }

    private function buildMetricWithComparisons(
        string $label,
        string $currentFormattedValue
    ): MetricWithComparisonsInterface {
        $currentMetric = $this->createMock(MetricResultInterface::class);
        $currentMetric->method('getFormattedValue')->willReturn($currentFormattedValue);

        $metricData = $this->createMock(MetricWithComparisonsInterface::class);
        $metricData->method('getMetricLabel')->willReturn($label);
        $metricData->method('getCurrent')->willReturn($currentMetric);

        return $metricData;
    }
}
