<?php

namespace GeoPagos\WithholdingTaxBundle\Contract;

use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;

interface RulesFacadeInterface
{
    public function findDynamicRule(WithholdingStageContext $context, SaleBag $saleBag): ?WithholdingTaxRuleInterface;

    public function findSimpleRule(WithholdingStageContext $context): ?WithholdingTaxRuleInterface;

    public function findHardRules(WithholdingStageContext $context): ?WithholdingTaxRuleInterface;

    public function isSubjectMarkedAsHabitual(WithholdingStageContext $context): bool;

    public function markSubjectAsHabitual(WithholdingStageContext $context): void;

    public function getLoggedTaxCategory(): ?TaxCategory;

    public function shouldUseUnpuslishRate(WithholdingStageContext $context): bool;
}
