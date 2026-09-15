<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Service;

use DateTimeImmutable;
use DateTimeZone;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;
use RunAsRoot\BusinessMetricsReport\Api\DateRangeServiceInterface;
use RunAsRoot\BusinessMetricsReport\Api\Data\DateRange;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DateRangeService implements DateRangeServiceInterface
{
    public function __construct(
        private readonly TimezoneInterface $timezone
    ) {
    }

    public function getDailySet(): DateRangeSet
    {
        return new DateRangeSet(
            currentRange: $this->getYesterdayRange(),
            weekAgoRange: $this->getSameDayLastWeek(),
            monthAgoRange: $this->getSameDayLastMonth(),
            yearAgoRange: $this->getSameDayLastYear(),
        );
    }

    public function getWeeklySet(): DateRangeSet
    {
        return new DateRangeSet(
            currentRange: $this->getPreviousWeekRange(),
            weekAgoRange: $this->getWeekBeforeLast(),
            monthAgoRange: $this->getSameWeekLastMonth(),
            yearAgoRange: $this->getSameWeekLastYear(),
        );
    }

    private function getYesterdayRange(): DateRange
    {
        $now = $this->storeNow();

        return new DateRange(
            startDate: $this->toUtc($now->modify('-1 day')->setTime(0, 0, 0)),
            endDate: $this->toUtc($now->modify('-1 day')->setTime(23, 59, 59)),
        );
    }

    private function getSameDayLastWeek(): DateRange
    {
        $yesterday = $this->getYesterdayRange();

        return new DateRange(
            startDate: $yesterday->startDate->modify('-7 days'),
            endDate: $yesterday->endDate->modify('-7 days'),
        );
    }

    private function getSameDayLastMonth(): DateRange
    {
        $yesterday = $this->getYesterdayRange();

        // -28 days instead of -1 month so the comparison window is always the same length
        // regardless of how many days are in the current or prior month.
        return new DateRange(
            startDate: $yesterday->startDate->modify('-28 days'),
            endDate: $yesterday->endDate->modify('-28 days'),
        );
    }

    private function getSameDayLastYear(): DateRange
    {
        $yesterday = $this->getYesterdayRange();

        return new DateRange(
            startDate: $yesterday->startDate->modify('-1 year'),
            endDate: $yesterday->endDate->modify('-1 year'),
        );
    }

    private function getPreviousWeekRange(): DateRange
    {
        $now = $this->storeNow();

        // ISO weekday: Mon=1 … Sun=7. % 7 maps Sunday (7) to 0, so ?: 7 keeps it as 7,
        // giving the number of days back to last Sunday for any day of the week.
        $dayOfWeek = ((int)$now->format('N') % 7) ?: 7;

        $lastSunday = $now->modify('-' . $dayOfWeek . ' days')->setTime(23, 59, 59);
        $lastMonday = $lastSunday->modify('-6 days')->setTime(0, 0, 0);

        return new DateRange(
            startDate: $this->toUtc($lastMonday),
            endDate: $this->toUtc($lastSunday),
        );
    }

    private function getWeekBeforeLast(): DateRange
    {
        $previousWeek = $this->getPreviousWeekRange();

        return new DateRange(
            startDate: $previousWeek->startDate->modify('-7 days'),
            endDate: $previousWeek->endDate->modify('-7 days'),
        );
    }

    private function getSameWeekLastMonth(): DateRange
    {
        $previousWeek = $this->getPreviousWeekRange();

        return new DateRange(
            startDate: $previousWeek->startDate->modify('-4 weeks'),
            endDate: $previousWeek->endDate->modify('-4 weeks'),
        );
    }

    private function getSameWeekLastYear(): DateRange
    {
        $previousWeek = $this->getPreviousWeekRange();

        return new DateRange(
            startDate: $previousWeek->startDate->modify('-52 weeks'),
            endDate: $previousWeek->endDate->modify('-52 weeks'),
        );
    }

    private function storeNow(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromMutable($this->timezone->date());
    }

    private function toUtc(DateTimeImmutable $storeDate): DateTimeImmutable
    {
        return $storeDate->setTimezone(new DateTimeZone('UTC'));
    }
}
