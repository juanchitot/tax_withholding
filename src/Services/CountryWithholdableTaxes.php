<?php

namespace GeoPagos\WithholdingTaxBundle\Services;

use GeoPagos\WithholdingTaxBundle\Model\Tax\Itbis;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Ganancias;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iibb;
use GeoPagos\WithholdingTaxBundle\Model\Tax\Iva;

class CountryWithholdableTaxes
{
    /**
     * @var ConfigurationManagerInterface
     */
    private $configurationManager;
    private $currentCountryId;

    /**
     * CountryWithholdableTaxes constructor.
     */
    public function __construct(ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
        $this->currentCountryId = $this->configurationManager->get('country')->getId();
    }

    public function getAvailableTaxes(): array
    {
        switch ($this->currentCountryId) {
            case 10: // Argentina
                return [Iva::class, Ganancias::class, Iibb::class];
            case 57: // Dominicana
                return [Itbis::class];
            default:
                return [];
        }
    }
}
