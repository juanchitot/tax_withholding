<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\Deposit;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\AddSaleToDepositRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\GenericSaleBagAdapter;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequestedResponse;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

final class AddSaleToDepositRequestedAdapter extends GenericSaleBagAdapter
{
    public function adaptResponse(SaleBag $saleBag): WithholdingTaxRequestedResponse
    {
        return new AddSaleToDepositRequestedResponse(
            $saleBag->getNetAmount()
        );
    }
}
