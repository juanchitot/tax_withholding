<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingProcessPipe;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface WithholdableTaxInterface
{
    public static function registerWithholdingStages(
        ContainerInterface $serviceLocator,
        WithholdingProcessPipe $processPipe
    ): void;

    public static function availableWithholdingStages(): array;
}
