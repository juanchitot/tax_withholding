<?php

namespace GeoPagos\WithholdingTaxBundle\Tests\Utils;

use GeoPagos\WithholdingTaxBundle\Tests\Integration\Scenes\SceneBuilder;

class WithholdingTaxCalculator
{
    public static function withhold(float $baseAmount, float $rate): float
    {
        return round($baseAmount * ($rate / 100), 2);
    }

    public static function calculateNetAmount(
        float $baseAmount,
        float $rate = null,
        float $trxComission = SceneBuilder::DEFAULT_TRANSACTIONS_COMMISSION,
        float $trxComissionTax = SceneBuilder::DEFAULT_TRANSACTIONS_COMMISSION_TAX
    ): float {
        $commissionAmount = round($baseAmount * $trxComission, 2);
        $commissionTaxAmount = round($commissionAmount * $trxComissionTax, 2);

        if ($rate) {
            $withheldAmount = self::withhold($baseAmount, $rate);
        } else {
            $withheldAmount = 0;
        }

        return $baseAmount - $commissionAmount - $commissionTaxAmount - $withheldAmount;
    }
}
