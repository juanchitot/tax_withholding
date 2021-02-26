<?php

namespace GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DigitalAccount;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequestedResponse;

class DebitAdjustmentRequestedResponse extends WithholdingTaxRequestedResponse
{
    /** @var array */
    private $withheldTaxes;

    public function __construct(float $netAmount, array $withheldTaxes)
    {
        parent::__construct($netAmount);
        $this->withheldTaxes = $withheldTaxes;
    }

    public function getWithheldTaxes(): array
    {
        return $this->withheldTaxes;
    }
}
