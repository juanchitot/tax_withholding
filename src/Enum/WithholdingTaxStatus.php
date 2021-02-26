<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class WithholdingTaxStatus extends BasicEnum
{
    public const __default = self::CREATING;
    public const CREATING = 'CREATING';
    public const CREATED = 'CREATED';
    public const TO_SET_CERTIFICATE_NUMBER = 'TO_SET_CERTIFICATE_NUMBER';
    public const SENT = 'SENT';
}
