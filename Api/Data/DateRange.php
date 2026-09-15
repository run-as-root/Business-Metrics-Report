<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

use DateTimeImmutable;

/**
 * @api
 */
class DateRange
{
    public function __construct(
        public readonly DateTimeImmutable $startDate,
        public readonly DateTimeImmutable $endDate,
    ) {
    }
}
