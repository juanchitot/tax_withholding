<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxRuleFileStatus extends BasicEnum
{
    const __default = self::PENDING;

    const PENDING = 'PENDING';
    const SUCCESS = 'SUCCESS';
    const FAILED = 'FAILED';
}
