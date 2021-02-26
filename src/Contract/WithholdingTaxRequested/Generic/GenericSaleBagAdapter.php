<?php

namespace GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic;

use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

abstract class GenericSaleBagAdapter implements SaleBagAdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function adaptRequest(WithholdingTaxRequested $request): SaleBag
    {
        return new SaleBag(
            $request->getTransactions(),
            $request->getCurrency(),
            $request->getAvailableDate()
        );
    }
}
