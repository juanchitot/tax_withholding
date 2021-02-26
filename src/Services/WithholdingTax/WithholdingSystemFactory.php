<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdingTax;

use Carbon\Carbon;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxSystem;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Agip;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Arba;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Atm;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sicore;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sircar;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sircar2;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sire;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingSystems\Sirtac;

abstract class WithholdingSystemFactory
{
    public static function get(ProvinceWithholdingTaxSetting $provinceWithholdingTaxSetting, Carbon $executionDate, ConfigurationManager $configurationManager)
    {
        switch ($provinceWithholdingTaxSetting->getWithholdingTaxSystem()) {
            case WithholdingTaxSystem::SIRCAR:
                return new Sircar($provinceWithholdingTaxSetting, $executionDate);

            case WithholdingTaxSystem::SIRCAR2:
                return new Sircar2($provinceWithholdingTaxSetting, $executionDate);

            case WithholdingTaxSystem::ATM:
                return new Atm($provinceWithholdingTaxSetting, $executionDate, $configurationManager->get('grouper_cuit'));

            case WithholdingTaxSystem::ARBA:
                return new Arba($provinceWithholdingTaxSetting, $executionDate);

            case WithholdingTaxSystem::AGIP:
                return new Agip($provinceWithholdingTaxSetting, $executionDate);

            case WithholdingTaxSystem::SIRTAC:
                return new Sirtac($provinceWithholdingTaxSetting, $executionDate);

            case WithholdingTaxSystem::VALUE_TAX_ADDED:
            case WithholdingTaxSystem::INCOME_TAX:
                return new Sicore($provinceWithholdingTaxSetting, $executionDate);

            // UNCOMMENT THIS WHEN SIRE IS OPERATIVE
            //case WithholdingTaxSystem::VALUE_TAX_ADDED:
            //    return new Sire($provinceWithholdingTaxSetting, $executionDate);

            default:
                return null;
        }
    }
}
