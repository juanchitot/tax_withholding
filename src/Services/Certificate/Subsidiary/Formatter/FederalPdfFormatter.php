<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Certificate\Subsidiary\Formatter;

use GeoPagos\ApiBundle\Entity\PaymentMethod;

class FederalPdfFormatter extends BaseEmailPdfFormatter
{
    protected function getParametersToReplace()
    {
        $params = parent::getParametersToReplace();
        $withholdingTax = $this->package->getData()[0];
        /* Overwrites */
        $params['showPaymentType'] = in_array($withholdingTax->getPaymentType(),
            [PaymentMethod::TYPE_DEBIT, PaymentMethod::TYPE_CREDIT],
            true);
        $params['showRate'] = $params['showPaymentType'];

        return $params;
    }
}
