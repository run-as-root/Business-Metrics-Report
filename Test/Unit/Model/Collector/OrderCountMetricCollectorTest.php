<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Model\Collector;

use DateTimeImmutable;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Model\Collector\OrderCountMetricCollector;
use RunAsRoot\BusinessMetricsReport\Model\Collector\OrderQueryFilter;
use RunAsRoot\BusinessMetricsReport\Model\Data\MetricResult;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrderCountMetricCollectorTest extends TestCase
{
    private ResourceConnection|MockObject $resourceConnection;
    private AdapterInterface|MockObject $connection;
    private Select|MockObject $select;
    private OrderQueryFilter|MockObject $orderQueryFilter;
    private MetricResultInterfaceFactory|MockObject $metricResultFactory;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->select = $this->createMock(Select::class);
        $this->orderQueryFilter = $this->createMock(OrderQueryFilter::class);
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

    private function createSut(): OrderCountMetricCollector
    {
        return new OrderCountMetricCollector(
            $this->resourceConnection,
            $this->orderQueryFilter,
            $this->metricResultFactory,
        );
    }

    public function testGetMetricCode(): void
    {
        $sut = $this->createSut();
        $this->assertSame('order_count', $sut->getMetricCode());
    }

    public function testGetMetricLabel(): void
    {
        $sut = $this->createSut();
        $this->assertSame('Order Count', $sut->getMetricLabel());
    }

    public function testGetSortOrder(): void
    {
        $sut = $this->createSut();
        $this->assertSame(20, $sut->getSortOrder());
    }

    public function testCollectAppliesOrderQueryFilter(): void
    {
        $this->connection->method('fetchOne')->willReturn('5');
        $this->orderQueryFilter->expects($this->once())
            ->method('apply')
            ->with($this->select)
            ->willReturn($this->select);

        $sut = $this->createSut();
        $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));
    }

    public function testCollectReturnsMetricCodeOrderCount(): void
    {
        $this->connection->method('fetchOne')->willReturn('5');

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertSame('order_count', $result->getMetricCode());
    }

    public function testCollectReturnsOrderCount(): void
    {
        $this->connection->method('fetchOne')->willReturn('7');

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertSame(7, $result->getValue());
        $this->assertSame('7 orders', $result->getFormattedValue());
    }

    public function testCollectReturnsZeroWhenNoOrders(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);

        $sut = $this->createSut();
        $result = $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));

        $this->assertSame(0, $result->getValue());
        $this->assertSame('0 orders', $result->getFormattedValue());
    }

    public function testCollectPreservesDateRange(): void
    {
        $startDate = new DateTimeImmutable('2025-01-01 00:00:00');
        $endDate = new DateTimeImmutable('2025-01-01 23:59:59');
        $this->connection->method('fetchOne')->willReturn('3');

        $sut = $this->createSut();
        $result = $sut->collect($startDate, $endDate);

        $this->assertEquals($startDate, $result->getStartDate());
        $this->assertEquals($endDate, $result->getEndDate());
    }

    public function testCollectQueriesSalesOrderTable(): void
    {
        $this->connection->method('fetchOne')->willReturn('0');
        $this->connection->expects($this->once())
            ->method('getTableName')
            ->with('sales_order')
            ->willReturn('sales_order');

        $sut = $this->createSut();
        $sut->collect(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01 23:59:59'));
    }
}
