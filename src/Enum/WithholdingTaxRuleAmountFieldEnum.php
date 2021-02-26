<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxRuleAmountFieldEnum extends BasicEnum
{
    const __default = self::NET;

    const GROSS_STRING = 'GROSS';
    const NET_STRING = 'NET';
    const NET_TAX_STRING = 'NET_TAX';
    const NET_COMMISSION_STRING = 'NET_COMMISSION';

    const GROSS = ['getAmount'];
    const NET = ['getAmountWithoutCommissionAndFinancialCost'];
    const NET_TAX = ['getAmountWithoutCommissionAndFinancialCost', 'getCommissionTaxAmount'];
    const NET_COMMISSION = ['getAmountWithoutCommissionAndFinancialCost', 'getCommissionAmount'];

    const GROSS_FIELD = '_t.amount';
    const NET_FIELD = '_t.amount - _t.commissionAmount - _t.commissionTaxAmount - _t.financialCostAmount';
    const NET_TAX_FIELD = '_t.amount - _t.commissionAmount - _t.financialCostAmount';
    const NET_COMMISSION_FIELD = '_t.amount - _t.commissionTaxAmount - _t.financialCostAmount';

    public static function getString($type)
    {
        switch ($type) {
            case self::GROSS_STRING: return 'Bruto';
            case self::NET_STRING: return 'Neto';
            case self::NET_TAX_STRING: return 'Neto + IVA Comisión';
            case self::NET_COMMISSION_STRING: return 'Neto + Comisión';
        }
    }
}
