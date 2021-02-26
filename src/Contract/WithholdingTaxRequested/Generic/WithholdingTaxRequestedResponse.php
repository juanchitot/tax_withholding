<?php

namespace GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic;

abstract class WithholdingTaxRequestedResponse
{
    /** @var float */
    private $netAmount;

    public function __construct(float $netAmount)
    {
        $this->netAmount = $netAmount;
    }

    public function getNetAmount(): float
    {
        return $this->netAmount;
    }
}
