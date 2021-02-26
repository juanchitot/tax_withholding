<?php

namespace GeoPagos\WithholdingTaxBundle\Helper;

class DiscretizationHelper
{
    const TWO_DECIMALS_PRECISION = 2;

    /**
     * - $number=10.333, $precision = 2  ->
     *      $number = truncate(1033.3)
     *      $number = 1033/100 = 10.33
     * - $number=10.35673, $precision = 3  ->
     *      $number = truncate(10356.73)
     *      $number = 10356/1000 = 10.356.
     *
     * @param $number
     * @param int $precision
     *
     * @return float|int
     */
    public static function truncate($number, $precision = self::TWO_DECIMALS_PRECISION)
    {
        $translate = pow(10, max($precision, 0));
        $number = floor($number * $translate);

        return $number / $translate;
    }

    public static function getRatePartFromAmount($rate, $amount, $precision = self::TWO_DECIMALS_PRECISION)
    {
        //TODO:
        // next we will change all the occurrences of this expression on the testing suite in favor
        // to the new way with the truncate function
        return round($amount * ($rate / 100), 2);
        //return self::truncate($amount * ($rate / 100), 2);
    }
}
