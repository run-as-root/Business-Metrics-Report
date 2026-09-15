<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

use DateTimeImmutable;

/**
 * @api
 */
interface MetricResultInterface
{
    public function getMetricCode(): string;

    public function getValue(): mixed;

    public function getFormattedValue(): string;

    public function getStartDate(): DateTimeImmutable;

    public function getEndDate(): DateTimeImmutable;

    public function hasValue(): bool;
}
