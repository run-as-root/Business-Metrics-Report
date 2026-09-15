<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\MetricWithComparisonsInterface;
use RunAsRoot\BusinessMetricsReport\Api\NotificationChannelInterface;
use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;
use RunAsRoot\BusinessMetricsReport\Model\Data\NotificationDispatchingResult;
use RunAsRoot\BusinessMetricsReport\System\Config\Config;

class NotificationDispatcher
{
    /**
     * @param NotificationChannelInterface[] $channels
     */
    public function __construct(
        private readonly array $channels,
        private readonly FluctuationDetector $fluctuationDetector,
        private readonly Config $config,
    ) {
    }

    /**
     * @param MetricWithComparisonsInterface[] $metricsData
     */
    public function dispatch(array $metricsData, string $reportType): NotificationDispatchingResult
    {
        if ($this->config->isOnlyOnFluctuationEnabled() && !$this->fluctuationDetector->hasFluctuation($metricsData)) {
            return new NotificationDispatchingResult([]);
        }

        $errors = [];

        foreach ($this->channels as $channel) {
            if (!$channel->isEnabled()) {
                continue;
            }

            try {
                $channel->notify($metricsData, $reportType);
            } catch (CannotSendMetricsDataNotificationException $e) {
                $errors[] = $e;
            } catch (\Throwable $e) {
                $errors[] = new CannotSendMetricsDataNotificationException(
                    $channel::class,
                    $reportType,
                    sprintf('Unexpected error in notification channel "%s": %s', $channel::class, $e->getMessage()),
                    $e
                );
            }
        }

        return new NotificationDispatchingResult($errors);
    }
}
