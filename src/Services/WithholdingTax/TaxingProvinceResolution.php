<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class TaxingProvinceResolution
{
    /**
     * @var ConfigurationManagerInterface
     */
    private $configurationManager;

    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param $type
     */
    public function transactionTaxingPointOfView(
        Transaction $transaction,
        $type
    ): object {
        $isTax = (WithholdingTaxTypeEnum::TAX === $type || WithholdingTaxTypeEnum::SIRTAC === $type);
        $isIibbConfigurablePerProvince = $this->configurationManager->isFeatureEnabled('is_iibb_configurable_per_province');
        $isInputModeFromPaymentButton = (StaticConstant::READ_MODE_ECOMMERCE === $transaction->getInputMode());

        $useBuyerProvince = $isTax && $isIibbConfigurablePerProvince && $isInputModeFromPaymentButton;

        $pointOfView = (object) ['useBuyerProvince' => $useBuyerProvince];
        if ($useBuyerProvince) {
            $pointOfView->province = $transaction->getTransactionDetail()->getProvince();
        } else {
            $pointOfView->province = $transaction->getSubsidiary()->getProvince();
        }

        return $pointOfView;
    }
}
