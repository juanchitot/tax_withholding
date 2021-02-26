<?php

namespace GeoPagos\WithholdingTaxBundle\Model\Tax;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdableTaxInterface;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingProcessPipe;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\IibbSirtacWithholdStage;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\IibbWithholdStage;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Iibb implements WithholdableTaxInterface
{
    public static function registerWithholdingStages(
        ContainerInterface $serviceLocator,
        WithholdingProcessPipe $processPipe
    ): void {
        $configurationManager = $serviceLocator->get(ConfigurationManagerInterface::class);
        if ($configurationManager->isFeatureEnabled('process_iibb')) {
            $processPipe->pushStage($serviceLocator->get(IibbWithholdStage::class));
            $processPipe->pushStage($serviceLocator->get(IibbSirtacWithholdStage::class));
        }
    }

    public static function availableWithholdingStages(): array
    {
        return [IibbWithholdStage::class, IibbSirtacWithholdStage::class];
    }

    public static function knownTaxTypes(): array
    {
        return [IibbWithholdStage::getTaxType(), IibbSirtacWithholdStage::getTaxType()];
    }

    public function groupDataForCertificate($data = [])
    {
        $knownWithholdCertificateEntries = [];
        $taxTypes = self::knownTaxTypes();
    }

    public function taxWithholdCertificateGenerator()
    {
        // TODO: Implement taxWithholdCertificateGenerator() method.
    }
}
