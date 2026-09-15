<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api\Data;

use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;

/**
 * @api
 */
class DateRangeSet
{
    public function __construct(
        public readonly DateRange $currentRange,
        public readonly DateRange $weekAgoRange,
        public readonly DateRange $monthAgoRange,
        public readonly DateRange $yearAgoRange,
    ) {
    }
}
