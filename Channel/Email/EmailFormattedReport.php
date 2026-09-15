<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailFormattedReportInterface;

class EmailFormattedReport implements EmailFormattedReportInterface
{
    /**
     * @param array<int, array<string, mixed>> $metrics
     */
    public function __construct(
        private readonly string $subject,
        private readonly string $headerTitle,
        private readonly string $headerPeriod,
        private readonly array $metrics,
    ) {
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getHeaderTitle(): string
    {
        return $this->headerTitle;
    }

    public function getHeaderPeriod(): string
    {
        return $this->headerPeriod;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }
}
