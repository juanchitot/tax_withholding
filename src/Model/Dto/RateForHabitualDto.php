<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Dto;

class RateForHabitualDto
{
    /** @var int */
    public $minimumTransactions;

    /** @var float */
    public $limit;

    /** @var float */
    public $minimumAmount;

    /** @var float */
    public $rate;
}
