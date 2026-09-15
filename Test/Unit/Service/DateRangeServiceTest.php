<?php

declare(strict_types=1);

namespace RunAsRoot\BusinessMetricsReport\Test\Unit\Service;

use RunAsRoot\BusinessMetricsReport\Api\Data\DateRangeSet;
use RunAsRoot\BusinessMetricsReport\Service\DateRangeService;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DateRangeServiceTest extends TestCase
{
    private DateRangeService $sut;
    private TimezoneInterface|MockObject $timezone;

    protected function setUp(): void
    {
        $this->timezone = $this->createMock(TimezoneInterface::class);
        $this->timezone->method('date')
            ->willReturn(new \DateTime('2024-06-15 10:00:00', new \DateTimeZone('UTC')));
        $this->timezone->method('convertConfigTimeToUtc')
            ->willReturnArgument(0);

        $this->sut = new DateRangeService($this->timezone);
    }

    public function testGetDailySetReturnsDateRangeSet(): void
    {
        $this->assertInstanceOf(DateRangeSet::class, $this->sut->getDailySet());
    }

    public function testGetDailySetCurrentRangeIsYesterdayFullDay(): void
    {
        $result = $this->sut->getDailySet();

        $this->assertSame('2024-06-14 00:00:00', $result->currentRange->startDate->format('Y-m-d H:i:s'));
        $this->assertSame('2024-06-14 23:59:59', $result->currentRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testGetDailySetWeekAgoRangeIsSevenDaysBeforeYesterday(): void
    {
        $result = $this->sut->getDailySet();

        $this->assertSame('2024-06-07 00:00:00', $result->weekAgoRange->startDate->format('Y-m-d H:i:s'));
        $this->assertSame('2024-06-07 23:59:59', $result->weekAgoRange->endDate->format('Y-m-d H:i:s'));
    }

    public function testGetDailySetMonthAgoRangeIsOneMonthBeforeYesterday(): void
    {
        $result = $this->sut->getDailySet();

        $this->assertSame('2024-05-17 00:00:00', $result->monthAgoRange->startDate->format('Y-m-d H:i:s'));
    }

    public function testGetDailySetYearAgoRangeIsOneYearBeforeYesterday(): void
    {
        $result = $this->sut->getDailySet();

        $this->assertSame('2023-06-14 00:00:00', $result->yearAgoRange->startDate->format('Y-m-d H:i:s'));
    }

    public function testGetDailySetDatesAreInUtc(): void
    {
        $result = $this->sut->getDailySet();

        $this->assertSame('UTC', $result->currentRange->startDate->getTimezone()->getName());
        $this->assertSame('UTC', $result->currentRange->endDate->getTimezone()->getName());
    }

    public function testGetDailySetConvertsStoreTzToUtc(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')
            ->willReturn(new \DateTime('2024-06-15 10:00:00', new \DateTimeZone('Europe/Berlin')));
        $timezone->method('convertConfigTimeToUtc')
            ->willReturnCallback(static function (string $datetime): string {
                return (new \DateTimeImmutable($datetime, new \DateTimeZone('Europe/Berlin')))
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s');
            });

        $sut = new DateRangeService($timezone);
        $result = $sut->getDailySet();

        $this->assertSame('2024-06-13 22:00:00', $result->currentRange->startDate->format('Y-m-d H:i:s'));
        $this->assertSame('2024-06-14 21:59:59', $result->currentRange->endDate->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $result->currentRange->startDate->getTimezone()->getName());
    }

    public function testGetWeeklySetReturnsDateRangeSet(): void
    {
        $this->assertInstanceOf(DateRangeSet::class, $this->sut->getWeeklySet());
    }

    public function testGetWeeklySetCurrentRangeStartsOnMonday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Monday', $result->currentRange->startDate->format('l'));
        $this->assertSame('00:00:00', $result->currentRange->startDate->format('H:i:s'));
    }

    public function testGetWeeklySetCurrentRangeEndsOnSunday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Sunday', $result->currentRange->endDate->format('l'));
        $this->assertSame('23:59:59', $result->currentRange->endDate->format('H:i:s'));
    }

    public function testGetWeeklySetCurrentRangeIsSevenDays(): void
    {
        $result = $this->sut->getWeeklySet();
        $start = $result->currentRange->startDate;
        $end = $result->currentRange->endDate;

        $this->assertSame(6, $start->diff($end)->days);
    }

    public function testGetWeeklySetDatesAreInUtc(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('UTC', $result->currentRange->startDate->getTimezone()->getName());
        $this->assertSame('UTC', $result->currentRange->endDate->getTimezone()->getName());
    }

    public function testGetWeeklySetWeekAgoRangeIsSevenDaysBeforePreviousWeek(): void
    {
        $result = $this->sut->getWeeklySet();
        $expectedStart = $result->currentRange->startDate->modify('-7 days');

        $this->assertSame($expectedStart->format('Y-m-d'), $result->weekAgoRange->startDate->format('Y-m-d'));
    }

    public function testGetWeeklySetMonthAgoRangeIsFourWeeksBeforePreviousWeek(): void
    {
        $result = $this->sut->getWeeklySet();
        $expectedStart = $result->currentRange->startDate->modify('-4 weeks');

        $this->assertSame($expectedStart->format('Y-m-d'), $result->monthAgoRange->startDate->format('Y-m-d'));
    }

    public function testGetWeeklySetMonthAgoRangeStartsOnMonday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Monday', $result->monthAgoRange->startDate->format('l'));
    }

    public function testGetWeeklySetMonthAgoRangeEndsOnSunday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Sunday', $result->monthAgoRange->endDate->format('l'));
    }

    public function testGetWeeklySetYearAgoRangeIsFiftyTwoWeeksBeforePreviousWeek(): void
    {
        $result = $this->sut->getWeeklySet();
        $expectedStart = $result->currentRange->startDate->modify('-52 weeks');

        $this->assertSame($expectedStart->format('Y-m-d'), $result->yearAgoRange->startDate->format('Y-m-d'));
    }

    public function testGetWeeklySetYearAgoRangeStartsOnMonday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Monday', $result->yearAgoRange->startDate->format('l'));
    }

    public function testGetWeeklySetYearAgoRangeEndsOnSunday(): void
    {
        $result = $this->sut->getWeeklySet();

        $this->assertSame('Sunday', $result->yearAgoRange->endDate->format('l'));
    }

    public function testGetWeeklySetOnSundayReturnsPreviousWeek(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('date')
            ->willReturn(new \DateTime('2024-06-16 10:00:00', new \DateTimeZone('UTC')));
        $timezone->method('convertConfigTimeToUtc')
            ->willReturnArgument(0);

        $sut = new DateRangeService($timezone);
        $result = $sut->getWeeklySet();

        $this->assertSame('2024-06-03', $result->currentRange->startDate->format('Y-m-d'));
        $this->assertSame('2024-06-09', $result->currentRange->endDate->format('Y-m-d'));
    }
}
