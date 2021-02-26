<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic;

use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;

abstract class GenericFederalStage extends GenericStage
{
    protected function taxMustNotInformEmptyRates(): bool
    {
        return true;
    }

    protected function findBaseRule(WithholdingStageContext $stageContext): ?WithholdingTaxRule
    {
        return $this->withholdingTaxRuleRepository->findOneBy([
            'type' => $this->getTaxType(),
        ]);
    }

    protected function appliesTaxableMinimumAmount(WithholdingStageContext $stageContext, SaleBag $saleBag): bool
    {
        return true;
    }

    protected function generateLogText(WithholdingStageContext $stageContext, WithholdingTaxLog $log): string
    {
        /** @var WithholdingTaxRuleInterface $applicableRule */
        $applicableRule = $stageContext->lastWithholdingMatchedRule();

        return "{$this->getTaxType()} {$applicableRule->getLogDescription($log)} {$stageContext->getApplicableRate()}%";
    }
}
