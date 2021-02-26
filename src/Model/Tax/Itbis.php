<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Tax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingProcessPipe;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\ItbisWithholdStage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Itbis implements WithholdableTaxInterface
{
    public static function registerWithholdingStages(
        ContainerInterface $serviceLocator,
        WithholdingProcessPipe $processPipe
    ): void {
        $configurationManager = $serviceLocator->get(ConfigurationManagerInterface::class);
        if ($configurationManager->isFeatureEnabled('process_itbis')) {
            $processPipe->pushStage($serviceLocator->get(ItbisWithholdStage::class));
        }
    }

    public static function availableWithholdingStages(): array
    {
        return [ItbisWithholdStage::class];
    }
}
