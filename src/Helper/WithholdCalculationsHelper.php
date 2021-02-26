<?php

namespace GeoPagos\WithholdingTaxBundle\Helper;

use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

class WithholdCalculationsHelper
{
    public static function getTaxableAmountFromTransactions(SaleBag $saleBag, WithholdingTaxRule $withholdingTax)
    {
        //base imponible
        $calculationBasis = (string) $withholdingTax->getCalculationBasis();

        $formula = constant("GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleAmountFieldEnum::$calculationBasis");

        $amount = 0;
        $transactions = $saleBag->getTransactions();

        foreach ($transactions as $trx) {
            foreach ($formula as $f) {
                $amount += $trx->{$f}();
            }
            if (Transaction::TYPE_REFUND === $trx->getTypeId()) {
                $amount = $amount * (-1);
            }
        }

        return $amount;
    }

    public static function getTaxableAmountFromRule(
        Transaction $transaction,
        WithholdingTaxRule $withholdingTaxRule,
        float $taxableCoefficient
    ) {
        $calculationBasis = (string) $withholdingTaxRule->getCalculationBasis();

        $formula = constant("GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleAmountFieldEnum::$calculationBasis");

        $amount = 0;
        foreach ($formula as $f) {
            $amount += $transaction->{$f}();
        }

        return $amount * $taxableCoefficient;
    }
}
