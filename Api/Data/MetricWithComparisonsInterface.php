<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

/**
 * @api
 */
interface MetricWithComparisonsInterface
{
    public function getMetricCode(): string;

    public function getMetricLabel(): string;

    public function getCurrent(): MetricResultInterface;

    public function getComparisons(): MetricComparisonsInterface;
}
