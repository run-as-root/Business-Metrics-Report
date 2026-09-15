<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Channel\Email\EmailFormattedReport;
use PHPUnit\Framework\TestCase;

class EmailFormattedReportTest extends TestCase
{
    private EmailFormattedReport $sut;

    protected function setUp(): void
    {
        $this->sut = new EmailFormattedReport(
            'Test Subject',
            'Daily Report',
            'Yesterday — Feb 18, 2026',
            [['metricLabel' => 'Revenue', 'currentValue' => '€100.00']],
        );
    }

    public function testGetSubject(): void
    {
        $this->assertSame('Test Subject', $this->sut->getSubject());
    }

    public function testGetHeaderTitle(): void
    {
        $this->assertSame('Daily Report', $this->sut->getHeaderTitle());
    }

    public function testGetHeaderPeriod(): void
    {
        $this->assertSame('Yesterday — Feb 18, 2026', $this->sut->getHeaderPeriod());
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->sut->getMetrics();

        $this->assertCount(1, $metrics);
        $this->assertSame('Revenue', $metrics[0]['metricLabel']);
        $this->assertSame('€100.00', $metrics[0]['currentValue']);
    }
}
