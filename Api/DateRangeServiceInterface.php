<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Api;

use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;

/**
 * @api
 */
interface DateRangeServiceInterface
{
    public function getDailySet(): DateRangeSet;

    public function getWeeklySet(): DateRangeSet;
}
