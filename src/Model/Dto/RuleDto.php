<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Dto;

class RuleDto
{
    /**
     * @var string
     */
    public $taxType;

    /**
     * @var int
     */
    public $provinceId;

    /**
     * @var string
     */
    public $provinceName;

    /**
     * @var bool
     */
    public $taxIsFederal;

    /**
     * @var string
     */
    public $calculationBasis;

    /**
     * @var bool
     */
    public $hasTaxRegistry;

    /**
     * @var float
     */
    public $unpublishedRate;

    /**
     * @var float
     */
    public $publishedMinimumAmount;

    /**
     * @var RateByConditionDto[]
     */
    public $ratesByCondition;

    /**
     * @var RateByCategoryDto[]
     */
    public $ratesByCategory;

    /**
     * @var ExcludedRateDto[]
     */
    public $excludedRates;

    /**
     * @var RateForHabitualDto[]
     */
    public $ratesForHabituals;
}
