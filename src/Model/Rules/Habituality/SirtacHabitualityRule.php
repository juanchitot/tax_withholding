<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Rules\Habituality;

use GeoPagos\WithholdingTaxBundle\Enum\HabitualityRulePeriodicityEnum;

final class SirtacHabitualityRule extends HabitualityRule
{
    public function __construct()
    {
        parent::__construct(
            3.00,
            0,
            10,
            50000,
            HabitualityRulePeriodicityEnum::THIS_MONTH
        );
    }
}
