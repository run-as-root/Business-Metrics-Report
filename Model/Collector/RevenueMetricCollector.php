<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Collector;

use DateTimeImmutable;
use DateTimeInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterfaceFactory;
use RunAsRoot\BusinessMetricsReport\Api\MetricCollectorInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\DateTime;
use Zend_Db_Expr;

class RevenueMetricCollector implements MetricCollectorInterface
{
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly OrderQueryFilter $orderQueryFilter,
        private readonly PricingHelper $pricingHelper,
        private readonly MetricResultInterfaceFactory $metricResultFactory,
    ) {
    }

    public function getMetricCode(): string
    {
        return 'revenue';
    }

    public function getMetricLabel(): string
    {
        return 'Revenue';
    }

    public function getSortOrder(): int
    {
        return 10;
    }

    public function collect(DateTimeInterface $startDate, DateTimeInterface $endDate): MetricResultInterface
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $connection->getTableName('sales_order');

        $select = $connection->select()
            ->from($table, ['total_revenue' => new Zend_Db_Expr('SUM(base_grand_total)')])
            ->where('created_at >= ?', $startDate->format(DateTime::DATETIME_PHP_FORMAT))
            ->where('created_at <= ?', $endDate->format(DateTime::DATETIME_PHP_FORMAT));

        $this->orderQueryFilter->apply($select, $this->getMetricCode());

        $revenue = (float) ($connection->fetchOne($select) ?: 0);

        return $this->metricResultFactory->create([
            'metricCode' => $this->getMetricCode(),
            'value' => $revenue,
            'formattedValue' => (string) $this->pricingHelper->currency($revenue, true, false),
            'startDate' => DateTimeImmutable::createFromInterface($startDate),
            'endDate' => DateTimeImmutable::createFromInterface($endDate),
        ]);
    }
}
