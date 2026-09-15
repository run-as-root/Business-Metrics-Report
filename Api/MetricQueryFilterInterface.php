<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api;

use Magento\Framework\DB\Select;

/**
 * @api
 */
interface MetricQueryFilterInterface
{
    public function apply(Select $select, string $metricCode): Select;
}
