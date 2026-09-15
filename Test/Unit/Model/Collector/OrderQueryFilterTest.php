<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Collector;

use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\BusinessMetricsReport\Api\MetricQueryFilterInterface;
use RunAsRoot\BusinessMetricsReport\Model\Collector\OrderQueryFilter;

class OrderQueryFilterTest extends TestCase
{
    public function testApplyCallsAllFiltersWithMetricCode(): void
    {
        $select = $this->createMock(Select::class);

        $filterA = $this->createMock(MetricQueryFilterInterface::class);
        $filterA->expects($this->once())->method('apply')->with($select, 'revenue')->willReturn($select);

        $filterB = $this->createMock(MetricQueryFilterInterface::class);
        $filterB->expects($this->once())->method('apply')->with($select, 'revenue')->willReturn($select);

        $sut = new OrderQueryFilter([$filterA, $filterB]);
        $result = $sut->apply($select, 'revenue');

        $this->assertSame($select, $result);
    }

    public function testApplyWithNoFiltersReturnsSelectUnchanged(): void
    {
        $select = $this->createMock(Select::class);
        $select->expects($this->never())->method($this->anything());

        $sut = new OrderQueryFilter([]);
        $result = $sut->apply($select, 'order_count');

        $this->assertSame($select, $result);
    }

    public function testApplyPassesMetricCodeToEachFilter(): void
    {
        $select = $this->createMock(Select::class);
        $receivedCodes = [];

        $filter = $this->createMock(MetricQueryFilterInterface::class);
        $filter->method('apply')
            ->willReturnCallback(static function (Select $s, string $code) use (&$receivedCodes, $select): Select {
                $receivedCodes[] = $code;
                return $select;
            });

        $sut = new OrderQueryFilter([$filter]);
        $sut->apply($select, 'my_metric');

        $this->assertSame(['my_metric'], $receivedCodes);
    }
}
