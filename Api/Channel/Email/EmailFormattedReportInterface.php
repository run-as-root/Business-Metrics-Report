<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Channel\Email;

/**
 * @api
 */
interface EmailFormattedReportInterface
{
    public function getSubject(): string;

    public function getHeaderTitle(): string;

    public function getHeaderPeriod(): string;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMetrics(): array;
}
