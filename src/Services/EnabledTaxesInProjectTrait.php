<?php

namespace GeoPagos\WithholdingTaxBundle\Services;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

trait EnabledTaxesInProjectTrait
{
    protected function getEnabledTaxesInProject(ConfigurationManagerInterface $configurationManager): array
    {
        $enabledTaxes = [];
        if ($configurationManager->isFeatureEnabled('process_vat')) {
            $enabledTaxes[] = WithholdingTaxTypeEnum::VAT;
        }
        if ($configurationManager->isFeatureEnabled('process_itbis')) {
            $enabledTaxes[] = WithholdingTaxTypeEnum::ITBIS;
        }
        if ($configurationManager->isFeatureEnabled('process_income_tax')) {
            $enabledTaxes[] = WithholdingTaxTypeEnum::INCOME_TAX;
        }
        if ($configurationManager->isFeatureEnabled('process_iibb')) {
            $enabledTaxes[] = WithholdingTaxTypeEnum::TAX;
        }

        return $enabledTaxes;
    }

    protected function areTaxesDisabled(ConfigurationManagerInterface $configurationManager): bool
    {
        return empty($this->getEnabledTaxesInProject($configurationManager));
    }
}
