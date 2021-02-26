<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\DigitalAccount;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DigitalAccount\DebitAdjustmentRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\GenericSaleBagAdapter;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

final class DebitAdjustmentRequestedAdapter extends GenericSaleBagAdapter
{
    public function adaptResponse(SaleBag $saleBag): WithholdingTaxRequestedResponse
    {
        return new DebitAdjustmentRequestedResponse(
            $saleBag->getNetAmount(),
            $saleBag->getWithheldTaxes()
        );
    }
}
