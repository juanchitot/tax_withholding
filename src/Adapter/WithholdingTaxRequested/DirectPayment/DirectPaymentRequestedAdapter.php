<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\DirectPayment;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DirectPayment\DirectPaymentRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\GenericSaleBagAdapter;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

final class DirectPaymentRequestedAdapter extends GenericSaleBagAdapter
{
    public function adaptResponse(SaleBag $saleBag): WithholdingTaxRequestedResponse
    {
        return new DirectPaymentRequestedResponse(
            $saleBag->getNetAmount()
        );
    }
}
