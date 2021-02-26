<?php

namespace GeoPagos\WithholdingTaxBundle\Enum;

use GeoPagos\ApiBundle\Enum\BasicEnum;

class TaxGenerationStrategyEnum extends BasicEnum
{
    public const __default = self::TRANSACTION_AVAILABLE_AT;

    const TRANSACTION_AVAILABLE_AT = 'transaction_available_at';
    const DEPOSIT_TO_DEPOSIT = 'deposit_to_deposit';
}
