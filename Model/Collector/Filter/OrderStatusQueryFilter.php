<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Collector\Filter;

use Magento\Framework\DB\Select;
use Magento\Sales\Model\Order;
use RunAsRoot\BusinessMetricsReport\Api\MetricQueryFilterInterface;

class OrderStatusQueryFilter implements MetricQueryFilterInterface
{
    public function apply(Select $select, string $metricCode): Select
    {
        return $select
            ->where('state = ?', Order::STATE_PROCESSING)
            ->where('base_grand_total > 0');
    }
}
