<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Email;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\NotificationChannelInterface;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Email\EmailReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;

class NotificationChannel implements NotificationChannelInterface
{
    /**
     * @param EmailReportFormatterInterface[] $formatters
     */
    public function __construct(
        private readonly array $formatters,
        private readonly Sender $sender,
        private readonly Config $config
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEmailEnabled();
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @throws CannotSendMetricsDataNotificationException
     */
    public function notify(array $metricsData, string $reportType): void
    {
        $formatter = $this->findFormatter($reportType);

        if (!$formatter) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                sprintf('No email formatter found for report type "%s"', $reportType)
            );
        }

        try {
            $report = $formatter->format($metricsData);
        } catch (\Throwable $e) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                sprintf('Failed to format email notification for report type "%s": %s', $reportType, $e->getMessage()),
                $e
            );
        }

        if (!$this->sender->send($report)) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                sprintf('Failed to send email notification for report type "%s"', $reportType)
            );
        }
    }

    private function findFormatter(string $reportType): ?EmailReportFormatterInterface
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->getReportType() === $reportType) {
                return $formatter;
            }
        }

        return null;
    }
}
