<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Collector;

use DateTimeImmutable;
use DateTimeInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime;
use Zend_Db_Expr;

class OrderCountMetricCollector implements MetricCollectorInterface
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderQueryFilter $orderQueryFilter,
        private readonly MetricResultInterfaceFactory $metricResultFactory,
    ) {
    }

    public function getMetricCode(): string
    {
        return 'order_count';
    }

    public function getMetricLabel(): string
    {
        return 'Order Count';
    }

    public function getSortOrder(): int
    {
        return 20;
    }

    public function collect(DateTimeInterface $startDate, DateTimeInterface $endDate): MetricResultInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sales_order');

        $select = $connection->select()
            ->from($table, ['order_count' => new Zend_Db_Expr('COUNT(*)')])
            ->where('created_at >= ?', $startDate->format(DateTime::DATETIME_PHP_FORMAT))
            ->where('created_at <= ?', $endDate->format(DateTime::DATETIME_PHP_FORMAT));

        $this->orderQueryFilter->apply($select, $this->getMetricCode());

        $orderCount = (int) ($connection->fetchOne($select) ?: 0);

        return $this->metricResultFactory->create([
            'metricCode' => $this->getMetricCode(),
            'value' => $orderCount,
            'formattedValue' => $orderCount . ' orders',
            'startDate' => DateTimeImmutable::createFromInterface($startDate),
            'endDate' => DateTimeImmutable::createFromInterface($endDate),
        ]);
    }
}
