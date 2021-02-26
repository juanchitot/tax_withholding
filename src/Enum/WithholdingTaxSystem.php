<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxSystem extends BasicEnum
{
    const __default = self::SIRCAR;

    const SIRCAR = 'SIRCAR';
    const SIRCAR2 = 'SIRCAR2';
    const ATM = 'ATM';
    const ARBA = 'ARBA';
    const AGIP = 'AGIP';
    const SIRTAC = 'SIRTAC';

    // FIX THIS WHEN SIRE IS OPERATIVE
    //const VALUE_TAX_ADDED = 'SIRE';
    const VALUE_TAX_ADDED = 'SICORE';
    const INCOME_TAX = 'SICORE';
}
