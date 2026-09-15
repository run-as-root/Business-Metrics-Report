<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Data;

use DateTimeImmutable;
use RunAsRoot\BusinessMetricsReport\Api\Data\MetricResultInterface;

class MetricResult implements MetricResultInterface
{
    public function __construct(
        private readonly string $metricCode,
        private readonly mixed $value,
        private readonly string $formattedValue,
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate,
    ) {
    }

    public function getMetricCode(): string
    {
        return $this->metricCode;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    public function getFormattedValue(): string
    {
        return $this->hasValue() ? $this->formattedValue : 'N/A';
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }
}
