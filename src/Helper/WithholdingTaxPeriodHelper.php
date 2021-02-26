<?php

namespace GeoPagos\WithholdingTaxBundle\Helper;

use Carbon\Carbon;
use GeoPagos\WithholdingTaxBundle\Enum\Period;
use GeoPagos\WithholdingTaxBundle\Exceptions\InvalidPeriodicityException;

class WithholdingTaxPeriodHelper
{
    /**
     * @throws InvalidPeriodicityException
     */
    public static function getDayInLastPeriod(string $periodicity, Carbon $executionDate = null): Carbon
    {
        $date = self::getNewDateInstance($executionDate);

        switch ($periodicity) {
            case Period::MONTHLY:
                $dayInLastPeriod = $date->startOfMonth()->subDay();

                break;
            case Period::SEMI_MONTHLY:
                if ($date->day > 15) {
                    $dayInLastPeriod = $date->day(2);
                } else {
                    $dayInLastPeriod = $date->startOfMonth()->subMonth()->day(17);
                }

                break;
            default:
                throw new InvalidPeriodicityException();
        }

        return $dayInLastPeriod;
    }

    public static function getNewDateInstance(Carbon $date = null): Carbon
    {
        return $date ? $date->copy() : Carbon::now();
    }

    public static function getMonthlyPeriodStartDate(Carbon $date = null): Carbon
    {
        return self::getNewDateInstance($date)->startOfMonth()->startOfDay();
    }

    public static function getMonthlyPeriodEndDate(Carbon $date = null): Carbon
    {
        return self::getNewDateInstance($date)->endOfMonth()->endOfDay();
    }

    public static function getSemiMonthlyPeriodStartDate(Carbon $date = null): Carbon
    {
        $dateCopy = self::getNewDateInstance($date);

        return ($dateCopy->day > 15) ? $dateCopy->day(16)->startOfDay() : $dateCopy->startOfMonth()->startOfDay();
    }

    public static function getSemiMonthlyPeriodEndDate(Carbon $date = null): Carbon
    {
        $dateCopy = self::getNewDateInstance($date);

        return ($dateCopy->day > 15) ? $dateCopy->endOfMonth()->endOfDay() : $dateCopy->day(15)->endOfDay();
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public static function getPeriodStartDate(string $periodicity, Carbon $date = null): Carbon
    {
        switch ($periodicity) {
            case Period::MONTHLY:
                $rtnValue = self::getMonthlyPeriodStartDate($date);

                break;
            case Period::SEMI_MONTHLY:
                $rtnValue = self::getSemiMonthlyPeriodStartDate($date);

                break;
            default:
                throw new InvalidPeriodicityException();
        }

        return $rtnValue;
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public static function getPeriodEndDate(string $periodicity, Carbon $date = null): Carbon
    {
        switch ($periodicity) {
            case Period::MONTHLY:
                $rtnValue = self::getMonthlyPeriodEndDate($date);

                break;
            case Period::SEMI_MONTHLY:
                $rtnValue = self::getSemiMonthlyPeriodEndDate($date);

                break;
            default:
                throw new InvalidPeriodicityException();
        }

        return $rtnValue;
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public static function getLastPeriodStartDate(string $periodicity, Carbon $date = null): Carbon
    {
        return self::getPeriodStartDate(
            $periodicity,
            self::getDayInLastPeriod($periodicity, $date)
        );
    }

    /**
     * @throws InvalidPeriodicityException
     */
    public static function getLastPeriodEndDate(string $periodicity, Carbon $date = null): Carbon
    {
        return self::getPeriodEndDate(
            $periodicity,
            self::getDayInLastPeriod($periodicity, $date)
        );
    }

    public static function getDateLimitsForCurrentMonth(Carbon $executionDate): array
    {
        $startDate = $executionDate->copy()->startOfMonth();
        $endDate = $executionDate->copy()->day(15)->endOfDay();

        return [$startDate, $endDate];
    }

    public static function getDateLimitsForPreviousMonth(Carbon $executionDate): array
    {
        $startDate = $executionDate->copy()->subMonth()->startOfMonth();
        $midDate = $executionDate->copy()->subMonth()->day(16)->startOfDay();
        $endDate = $executionDate->copy()->subMonth()->endOfMonth();

        return [$startDate, $midDate, $endDate];
    }
}
