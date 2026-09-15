<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Channel\Slack;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\NotificationChannelInterface;
use RunAsRoot\BusinessMetricsReport\Api\Channel\Slack\SlackReportFormatterInterface;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\Exception\SlackApiException;
use RunAsRoot\BusinessMetricsReport\Service\FluctuationDetector;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;

class NotificationChannel implements NotificationChannelInterface
{
    /**
     * @param SlackReportFormatterInterface[] $formatters
     */
    public function __construct(
        private readonly Client $client,
        private readonly array $formatters,
        private readonly Config $config,
        private readonly FluctuationDetector $fluctuationDetector,
        private readonly SummaryMessageBuilder $summaryMessageBuilder
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isSlackEnabled();
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     * @throws CannotSendMetricsDataNotificationException
     */
    public function notify(array $metricsData, string $reportType): void
    {
        $channelId = $this->config->getChannelId();
        if (!$channelId) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                'Slack channel ID is not configured'
            );
        }

        $formatter = $this->findFormatter($reportType);

        if (!$formatter) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                sprintf('No Slack formatter found for report type "%s"', $reportType)
            );
        }

        $hasFluctuation = $this->fluctuationDetector->hasFluctuation($metricsData);
        $summaryPayload = $this->summaryMessageBuilder->build($reportType, $hasFluctuation);
        $summaryPayload['channel'] = $channelId;

        try {
            $messageTs = $this->client->post($summaryPayload);
        } catch (SlackApiException $e) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                'Failed to send Slack summary message: ' . $e->getMessage(),
                $e
            );
        }

        try {
            $threadPayload = $formatter->format($metricsData);
        } catch (\Throwable $e) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                sprintf('Failed to format Slack thread for report type "%s": %s', $reportType, $e->getMessage()),
                $e
            );
        }

        $threadPayload['channel'] = $channelId;
        $threadPayload['thread_ts'] = $messageTs;

        try {
            $this->client->post($threadPayload);
        } catch (SlackApiException $e) {
            throw new CannotSendMetricsDataNotificationException(
                self::class,
                $reportType,
                'Failed to send Slack thread reply: ' . $e->getMessage(),
                $e
            );
        }
    }

    private function findFormatter(string $reportType): ?SlackReportFormatterInterface
    {
        foreach ($this->formatters as $formatter) {
            if ($formatter->getReportType() === $reportType) {
                return $formatter;
            }
        }

        return null;
    }
}
