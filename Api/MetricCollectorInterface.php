<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;

/**
 * @api
 */
interface MetricCollectorInterface
{
    public function getMetricCode(): string;

    public function getMetricLabel(): string;

    /**
     * @param \DateTimeInterface $startDate UTC
     * @param \DateTimeInterface $endDate UTC
     */
    public function collect(\DateTimeInterface $startDate, \DateTimeInterface $endDate): MetricResultInterface;

    public function getSortOrder(): int;
}
