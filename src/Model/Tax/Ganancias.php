<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Tax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingProcessPipe;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\GananciasWithholdStage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Ganancias implements WithholdableTaxInterface
{
    public static function registerWithholdingStages(
        ContainerInterface $serviceLocator,
        WithholdingProcessPipe $processPipe
    ): void {
        $configurationManager = $serviceLocator->get(ConfigurationManagerInterface::class);
        if ($configurationManager->isFeatureEnabled('process_income_tax')) {
            $processPipe->pushStage($serviceLocator->get(GananciasWithholdStage::class));
        }
    }

    public static function availableWithholdingStages(): array
    {
        return [GananciasWithholdStage::class];
    }
}
