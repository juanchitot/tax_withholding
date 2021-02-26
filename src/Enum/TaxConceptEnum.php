<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class TaxConceptEnum extends BasicEnum
{
    const WITHHOLDING_ID = 1;
    const INFORMATIVE_ID = 2;
    const EXCLUDED_ID = 3;
    const UNREGISTERED_ID = 4;
    const PENALTY_ID = 5;
}
