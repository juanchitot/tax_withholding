<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Dto;

class RateByCategoryDto
{
    /** @var string */
    public $category;

    /** @var float */
    public $taxableAmountCoefficient;

    /** @var float */
    public $minimumAmount;

    /** @var float */
    public $rate;
}
