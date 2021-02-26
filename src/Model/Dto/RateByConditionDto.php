<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Dto;

class RateByConditionDto
{
    /** @var string */
    public $condition;

    /** @var float */
    public $taxableAmountCoefficient;

    /** @var float */
    public $minimumAmount;

    /** @var string */
    public $paymentMethodType;

    /** @var string */
    public $businessActivity;

    /** @var float */
    public $rate;
}
