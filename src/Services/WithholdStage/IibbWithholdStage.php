<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage;

use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdCalculationsHelper;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic\GenericStage;

final class IibbWithholdStage extends GenericStage
{
    public static function getTaxType(): string
    {
        return WithholdingTaxTypeEnum::TAX;
    }

    protected function isSubjectConfiguredAsExcluded(WithholdingStageContext $stageContext): bool
    {
        return false;
    }

    protected function taxMustNotInformEmptyRates(): bool
    {
        return true;
    }

    protected function findBaseRule(WithholdingStageContext $stageContext): ?WithholdingTaxRule
    {
        return $this->withholdingTaxRuleRepository->findOneBy([
            'type' => self::getTaxType(),
            'province' => $stageContext->getTransactionTaxingPointOfView()->province,
        ]);
    }

    protected function appliesTaxableMinimumAmount(WithholdingStageContext $stageContext, SaleBag $saleBag): bool
    {
        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $stageContext->firstWithholdingMatchedRule();

        $taxableAmountFromTransactions = WithholdCalculationsHelper::getTaxableAmountFromTransactions(
            $saleBag,
            $withholdingTaxRule
        );

        if ($this->rulesFacade->shouldUseUnpuslishRate($stageContext)) {
            if ($taxableAmountFromTransactions < $withholdingTaxRule->getMinimumDynamicRuleAmount()) {
                $this->log('Minimum amount not reached', $stageContext);

                return false;
            }
            $stageContext->setApplicableRate($withholdingTaxRule->getUnpublishRate());
        }

        $applicableRule = $stageContext->lastWithholdingMatchedRule();
        if ($applicableRule->shouldSkipByMinimumAmount($withholdingTaxRule, $saleBag, $taxableAmountFromTransactions)) {
            $minimumAmount = $applicableRule->calculateRequiredMinimumAmount(
                $withholdingTaxRule,
                $saleBag,
                $taxableAmountFromTransactions
            );
            $this->log('Withhold skipped by minimum amount '.$minimumAmount, $stageContext);

            return false;
        }

        if (parent::applicableRuleDoesntHaveRate($stageContext)) {
            $this->log('Applicable rate is 0 for the given context', $stageContext);

            return false;
        }

        return true;
    }

    protected function updateContextWithApplicableRule(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        parent::updateContextWithApplicableRule($stageContext, $saleBag);
        if (null !== $this->rulesFacade->getLoggedTaxCategory()) {
            $stageContext->setLoggedTaxCategory($this->rulesFacade->getLoggedTaxCategory());
        }
    }

    protected function generateLogText(WithholdingStageContext $stageContext, WithholdingTaxLog $log): string
    {
        /** @var WithholdingTaxRuleInterface $applicableRule */
        $applicableRule = $stageContext->lastWithholdingMatchedRule();
        /** @var Province $province */
        $province = $stageContext->getTransactionTaxingPointOfView()->province;

        return "Retención Impuesto Ingresos Brutos [{$province->getName()}] - {$applicableRule->getLogDescription($log)}";
    }
}
