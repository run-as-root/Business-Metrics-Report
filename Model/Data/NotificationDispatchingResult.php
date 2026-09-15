<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Model\Data;

use RunAsRoot\BusinessMetricsReport\Api\Exception\CannotSendMetricsDataNotificationException;

class NotificationDispatchingResult
{
    /**
     * @param CannotSendMetricsDataNotificationException[] $errors
     */
    public function __construct(private readonly array $errors)
    {
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return CannotSendMetricsDataNotificationException[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
