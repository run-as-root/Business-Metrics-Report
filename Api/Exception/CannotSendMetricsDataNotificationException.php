<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Exception;

/**
 * @api
 */
class CannotSendMetricsDataNotificationException extends \RuntimeException
{
    public function __construct(
        private readonly string $channelClass,
        private readonly string $reportType,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getChannelClass(): string
    {
        return $this->channelClass;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }
}
