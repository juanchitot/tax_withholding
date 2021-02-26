<?php

namespace GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested;

use GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\Deposit\AddSaleToDepositRequestedAdapter;
use GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\Deposit\DepositTransferRequestedAdapter;
use GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\DigitalAccount\DebitAdjustmentRequestedAdapter;
use GeoPagos\WithholdingTaxBundle\Adapter\WithholdingTaxRequested\DirectPayment\DirectPaymentRequestedAdapter;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\AddSaleToDepositRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Deposit\DepositTransferRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DigitalAccount\DebitAdjustmentRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\DirectPayment\DirectPaymentRequested;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\SaleBagAdapterInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRequested\Generic\WithholdingTaxRequested;

/**
 * Factory method that returns a concrete adapter
 * from a given domain event related to a request of withholding tax.
 */
final class WithholdingTaxRequestedAdapterCreator
{
    public function getAdapter(WithholdingTaxRequested $request): SaleBagAdapterInterface
    {
        switch ($this->getRequestType($request)) {
            case AddSaleToDepositRequested::class:
                return new AddSaleToDepositRequestedAdapter();
            case DepositTransferRequested::class:
                return new DepositTransferRequestedAdapter();
            case DirectPaymentRequested::class:
                return new DirectPaymentRequestedAdapter();
            case DebitAdjustmentRequested::class:
                return new DebitAdjustmentRequestedAdapter();
        }
    }

    private function getRequestType(WithholdingTaxRequested $request): string
    {
        return get_class($request);
    }
}
