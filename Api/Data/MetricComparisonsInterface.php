<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

/**
 * @api
 */
interface MetricComparisonsInterface
{
    public function getWeekAgo(): MetricResultInterface;

    public function getMonthAgo(): MetricResultInterface;

    public function getYearAgo(): MetricResultInterface;

    public function hasAnyComparisons(): bool;
}
