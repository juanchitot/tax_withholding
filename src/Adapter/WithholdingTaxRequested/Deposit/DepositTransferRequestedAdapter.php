<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\Deposit;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\DepositTransferRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\GenericSaleBagAdapter;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

final class DepositTransferRequestedAdapter extends GenericSaleBagAdapter
{
    public function adaptResponse(SaleBag $saleBag): WithholdingTaxRequestedResponse
    {
        return new DepositTransferRequestedResponse(
            $saleBag->getNetAmount()
        );
    }
}
