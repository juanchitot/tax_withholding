<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Tax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingProcessPipe;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\IvaWithholdStage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Iva implements WithholdableTaxInterface
{
    public static function registerWithholdingStages(
        ContainerInterface $serviceLocator,
        WithholdingProcessPipe $processPipe
    ): void {
        $configurationManager = $serviceLocator->get(ConfigurationManagerInterface::class);
        if ($configurationManager->isFeatureEnabled('process_vat')) {
            $processPipe->pushStage($serviceLocator->get(IvaWithholdStage::class));
        }
    }

    public static function availableWithholdingStages(): array
    {
        return [IvaWithholdStage::class];
    }

    public function taxWithholdCertificateGenerator()
    {
        // TODO: Implement taxWithholdCertificateGenerator() method.
    }
}
