<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Collector;

use Magento\Framework\DB\Select;
use RunAsRoot\BusinessMetricsReport\Api\MetricQueryFilterInterface;

class OrderQueryFilter
{
    /**
     * @param MetricQueryFilterInterface[] $filters
     */
    public function __construct(private readonly array $filters = [])
    {
    }

    public function apply(Select $select, string $metricCode): Select
    {
        foreach ($this->filters as $filter) {
            $filter->apply($select, $metricCode);
        }

        return $select;
    }
}
