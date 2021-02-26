<?php

namespace GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic;

use GeoPagos\WithholdingTaxBundle\Exceptions\EmptyTransactionException;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;

interface SaleBagAdapterInterface
{
    /**
     * @throws EmptyTransactionException
     */
    public function adaptRequest(WithholdingTaxRequested $request): SaleBag;

    public function adaptResponse(SaleBag $saleBag): WithholdingTaxRequestedResponse;
}
