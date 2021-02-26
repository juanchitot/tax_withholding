<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

interface WithholdingTaxRuleInterface
{
    public function shouldSkipByMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): bool;

    public function calculateRequiredMinimumAmount(
        WithholdingTaxRule $withholdingTaxRule,
        SaleBag $saleBag,
        $taxableAmountFromTransactions
    ): float;

    public function getRate();

    public function getLogDescription(WithholdingTaxLog $log): string;
}
