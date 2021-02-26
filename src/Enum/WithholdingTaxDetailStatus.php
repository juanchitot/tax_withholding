<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxDetailStatus extends BasicEnum
{
    public const __default = self::PENDING;

    public const PENDING = 'PENDING';
    public const PROCESSING = 'PROCESSING';
    public const SUCCESS = 'SUCCESS';
}
