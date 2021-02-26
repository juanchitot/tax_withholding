<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class Period extends BasicEnum
{
    public const __default = self::MONTHLY;

    public const MONTHLY = 'MONTHLY';
    public const SEMI_MONTHLY = 'SEMI_MONTHLY';
}
