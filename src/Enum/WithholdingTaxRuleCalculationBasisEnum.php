<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxRuleCalculationBasisEnum extends BasicEnum
{
    const __default = self::NET;

    const GROSS = 'GROSS';
    const NET = 'NET';
    const NET_TAX = 'NET_TAX';
    const NET_COMMISSION = 'NET_COMMISSION';
}
