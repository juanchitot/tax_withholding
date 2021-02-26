<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Dto;

class TaxAmountDto
{
    /** @var string */
    public $name;

    /** @var float */
    public $amount;

    /**
     * TaxAmountDto constructor.
     */
    public function __construct(string $name, float $amount)
    {
        $this->name = $name;
        $this->amount = $amount;
    }
}
