<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Collector\Filter;

use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use RunAsRoot\BusinessMetricsReport\Model\Collector\Filter\OrderStatusQueryFilter;

class OrderStatusQueryFilterTest extends TestCase
{
    public function testApplyAddsStatusAndGrandTotalConditions(): void
    {
        $select = $this->createMock(Select::class);
        $calls = [];
        $select->expects($this->exactly(2))
            ->method('where')
            ->willReturnCallback(function () use (&$calls, $select) {
                $calls[] = func_get_args();
                return $select;
            });
        // assertions after apply()
        $sut = new OrderStatusQueryFilter();
        $result = $sut->apply($select, 'revenue');

        $this->assertSame($select, $result);
        $this->assertSame(['state = ?', Order::STATE_PROCESSING, null], $calls[0]);
        $this->assertSame(['base_grand_total > 0', null, null], $calls[1]);
    }

    public function testApplyIgnoresMetricCode(): void
    {
        $select = $this->createMock(Select::class);
        $select->method('where')->willReturnSelf();

        $sut = new OrderStatusQueryFilter();
        $resultA = $sut->apply($select, 'revenue');
        $resultB = $sut->apply($select, 'order_count');

        $this->assertSame($select, $resultA);
        $this->assertSame($select, $resultB);
    }
}
