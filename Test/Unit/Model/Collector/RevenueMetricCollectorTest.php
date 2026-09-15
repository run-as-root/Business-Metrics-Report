<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Collector;

use DateTimeImmutable;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Model\Collector\OrderQueryFilter;
use RunAsRoot\BusinessMetricsReport\Model\Collector\RevenueMetricCollector;
use RunAsRoot\BusinessMetricsReport\Model\Data\MetricResult;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RevenueMetricCollectorTest extends TestCase
{
    private ResourceConnection|MockObject $resourceConnection;
    private AdapterInterface|MockObject $connection;
    private Select|MockObject $select;
    private OrderQueryFilter|MockObject $orderQueryFilter;
    private PricingHelper|MockObject $pricingHelper;
    private MetricResultInterfaceFactory|MockObject $metricResultFactory;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->select = $this->createMock(Select::class);
        $this->orderQueryFilter = $this->createMock(OrderQueryFilter::class);
        $this->pricingHelper = $this->createMock(PricingHelper::class);
        $this->metricResultFactory = $this->createMock(MetricResultInterfaceFactory::class);
        $this->metricResultFactory->method('create')->willReturnCallback(
            static fn (array $data) => new MetricResult(
                $data['metricCode'],
                $data['value'],
                $data['formattedValue'],
                $data['startDate'],
                $data['endDate'],
            )
        );

        $this->resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->connection->method('getTableName')->willReturnArgument(0);
        $this->connection->method('select')->willReturn($this->select);
        $this->select->method('from')->willReturnSelf();
        $this->select->method('where')->willReturnSelf();
        $this->orderQueryFilter->method('apply')->willReturnArgument(0);
    }

    private function createSut(): RevenueMetricCollector
    {
        return new RevenueMetricCollector(
            $this->resourceConnection,
            $this->orderQueryFilter,
            $this->pricingHelper,
            $this->metricResultFactory,
        );
    }

    public function testGetMetricCode(): void
    {
        $sut = $this->createSut();
        $this->assertSame('revenue', $sut->getMetricCode());
    }

    public function testGetMetricLabel(): void
    {
        $sut = $this->createSut();
        $this->assertSame('Revenue', $sut->getMetricLabel());
    }

    public function testGetSortOrder(): void
    {
        $sut = $this->createSut();
        $this->assertSame(10, $sut->getSortOrder());
    }

    public function testCollectAppliesOrderQueryFilter(): void
    {
        $this->connection->method('fetchOne')->willReturn('1500.00');
        $this->pricingHelper->method('currency')->willReturn('$1,500.00');
        $this->orderQueryFilter->expects($this->once())
            ->method('apply')
            ->with($this->select)
            ->willReturn($this->select);

        $sut = $this->createSut();
        $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));
    }

    public function testCollectReturnsMetricCodeRevenue(): void
    {
        $this->connection->method('fetchOne')->willReturn('1500.00');
        $this->pricingHelper->method('currency')->willReturn('$1,500.00');

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertSame('revenue', $result->getMetricCode());
    }

    public function testCollectReturnsRevenue(): void
    {
        $this->connection->method('fetchOne')->willReturn('1500.00');
        $this->pricingHelper->method('currency')->with(1500.0, true, false)->willReturn('$1,500.00');

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertEqualsWithDelta(1500.0, $result->getValue(), 0.001);
        $this->assertSame('$1,500.00', $result->getFormattedValue());
    }

    public function testCollectReturnsZeroRevenueWhenNoOrders(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);
        $this->pricingHelper->method('currency')->willReturn('$0.00');

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertEqualsWithDelta(0.0, $result->getValue(), 0.001);
    }

    public function testCollectPreservesDateRange(): void
    {
        $startDate = new DateTimeImmutable('2025-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2025-01-01 23:59:59');
        $this->connection->method('fetchOne')->willReturn('100.00');
        $this->pricingHelper->method('currency')->willReturn('$100.00');

        $sut = $this->createSut();
        $result = $sut->collect($startDate, $endDate);

        $this->assertEquals($startDate, $result->getStartDate());
        $this->assertEquals($endDate, $result->getEndDate());
    }

    public function testCollectQueriesSalesOrderTable(): void
    {
        $this->connection->method('fetchOne')->willReturn('0');
        $this->pricingHelper->method('currency')->willReturn('$0.00');
        $this->connection->expects($this->once())
            ->method('getTableName')
            ->with('sales_order')
            ->willReturn('sales_order');

        $sut = $this->createSut();
        $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));
    }
}
