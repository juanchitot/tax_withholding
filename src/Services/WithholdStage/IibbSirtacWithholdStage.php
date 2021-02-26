<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Enum\TaxCategoryCode;
use GeoPagos\ApiBundle\Repository\TransactionRepository;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroup;
use GeoPagos\WithholdingTaxBundle\Entity\TaxRuleProvincesGroupItem;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use GeoPagos\WithholdingTaxBundle\Enum\TaxRuleProvincesGroupEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Helper\SirtacJurisdictionsHelper;
use GeoPagos\WithholdingTaxBundle\Model\Rules\Habituality\SirtacHabitualityRule;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Repository\TaxRuleProvincesGroupRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxExclusionRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\Rules\RulesFacade;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\TaxingProvinceResolution;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxSettingsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic\GenericStage;
use Psr\Log\LoggerInterface;

final class IibbSirtacWithholdStage extends GenericStage
{
    private const PENALTY_RATE = 1.50;

    /** @var TaxRuleProvincesGroup */
    private $sirtacProvinceGroup;

    /** @var TransactionRepository */
    private $transactionRepository;

    /** @var SirtacHabitualityRule */
    private $habitualityRule;

    public function __construct(
        TransactionRepository $transactionRepository,
        TaxRuleProvincesGroupRepository $taxRuleProvincesGroupRepository,
        WithholdingTaxExclusionRepository $withholdingTaxExclusionRepository,
        WithholdingTaxSettingsService $withholdingTaxSettingsService,
        WithholdingTaxRuleRepository $withholdingTaxRuleRepository,
        TaxingProvinceResolution $taxingProvinceResolution,
        ConfigurationManagerInterface $configurationManager,
        RulesFacade $rulesFacade,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->initializeConcreteDependencies(
            $transactionRepository,
            $taxRuleProvincesGroupRepository
        );
        parent::__construct(
            $withholdingTaxExclusionRepository,
            $withholdingTaxSettingsService,
            $withholdingTaxRuleRepository,
            $taxingProvinceResolution,
            $configurationManager,
            $rulesFacade,
            $entityManager,
            $logger
        );
    }

    private function initializeConcreteDependencies(
        TransactionRepository $transactionRepository,
        TaxRuleProvincesGroupRepository $taxRuleProvincesGroupRepository
    ): void {
        $this->transactionRepository = $transactionRepository;
        $this->sirtacProvinceGroup = $taxRuleProvincesGroupRepository->find(
            TaxRuleProvincesGroupEnum::SIRTAC_ID
        );
        $this->habitualityRule = new SirtacHabitualityRule();
    }

    public static function getTaxType(): string
    {
        return WithholdingTaxTypeEnum::SIRTAC;
    }

    protected function taxMustNotInformEmptyRates(): bool
    {
        return false;
    }

    protected function isSubjectConfiguredAsExcluded(WithholdingStageContext $stageContext): bool
    {
        return false;
    }

    protected function appliesTaxableMinimumAmount(WithholdingStageContext $stageContext, SaleBag $saleBag): bool
    {
        return true;
    }

    protected function findBaseRule(WithholdingStageContext $stageContext): ?WithholdingTaxRule
    {
        return $this->withholdingTaxRuleRepository->findOneBy([
            'type' => $this->getTaxType(),
        ]);
    }

    protected function updateContextWithApplicableRule(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        $this->rulesFacade->findDynamicRule($stageContext, $saleBag) ||
        $this->rulesFacade->findSimpleRule($stageContext);

        if (null !== $this->rulesFacade->getLoggedTaxCategory()) {
            $stageContext->setLoggedTaxCategory($this->rulesFacade->getLoggedTaxCategory());
        }
    }

    protected function doWithholdingTax(WithholdingStageContext $stageContext, SaleBag $saleBag, int $taxConceptId): bool
    {
        if ($this->isNotSubjectInTaxRegistry($stageContext) && $this->isNotSaleMadeInASirtacProvince($stageContext)) {
            return false;
        }

        if ($this->isSubjectInTaxRegistryAsRegistered($stageContext)) {
            $this->doRegisteredTax($stageContext, $saleBag);

            return true;
        }

        if ($this->isSubjectInTaxRegistryAsUnregistered($stageContext)) {
            $this->doRegisteredTax($stageContext, $saleBag);
            $this->doPenaltyTax($stageContext, $saleBag);

            return true;
        }

        if ($this->isNotSubjectInTaxRegistry($stageContext) && $this->isSaleMadeInASirtacProvince($stageContext)) {
            if ($this->isSubjectRegisteredAsExempt($stageContext)) {
                return false;
            }

            if ($this->isSubjectRegisteredInAnyProvince($stageContext)) {
                $this->doPenaltyTax($stageContext, $saleBag);
            } else {
                $this->doUnregisteredTax($stageContext, $saleBag);
            }

            return true;
        }

        return false;
    }

    private function doRegisteredTax(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        if (parent::applicableRuleDoesntHaveRate($stageContext)) {
            parent::doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::INFORMATIVE_ID);
        } else {
            parent::doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::WITHHOLDING_ID);
        }
    }

    private function doPenaltyTax(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        $stageContext->setApplicableRate(self::PENALTY_RATE);
        parent::doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::PENALTY_ID);
    }

    private function doUnregisteredTax(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        if ($this->rulesFacade->isSubjectMarkedAsHabitual($stageContext)) {
            $this->updateContextWithHabitualityRule($stageContext);
            parent::doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::UNREGISTERED_ID);

            return;
        }

        if ($this->isSubjectApplyingToHabituality($stageContext)) {
            $this->rulesFacade->markSubjectAsHabitual($stageContext);
            $this->updateContextWithHabitualityRule($stageContext);
            parent::doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::UNREGISTERED_ID);
        }
    }

    private function updateContextWithHabitualityRule(WithholdingStageContext $stageContext): void
    {
        $stageContext->setApplicableRate($this->habitualityRule->getRate());
        $stageContext->pushWithholdingMatchedRule($this->habitualityRule);
    }

    private function isSubjectApplyingToHabituality(WithholdingStageContext $stageContext): bool
    {
        return $this->transactionRepository->isSubsidiaryApplyingToHabituality(
            $stageContext->getSubsidiary(),
            $this->habitualityRule,
            $this->sirtacProvinceGroup,
            $stageContext->getTransactionTaxingPointOfView()->useBuyerProvince
        );
    }

    private function isSubjectInTaxRegistry(WithholdingStageContext $stageContext): bool
    {
        $applicableRule = $stageContext->lastWithholdingMatchedRule();

        return $applicableRule instanceof WithholdingTaxDynamicRule;
    }

    private function isNotSubjectInTaxRegistry(WithholdingStageContext $stageContext): bool
    {
        return !$this->isSubjectInTaxRegistry($stageContext);
    }

    private function isSaleMadeInASirtacProvince(WithholdingStageContext $stageContext): bool
    {
        $groupItems = $this->sirtacProvinceGroup->getGroupItems();
        $saleProvince = $stageContext->getTransactionTaxingPointOfView()->province;

        return $groupItems->exists(function ($key, TaxRuleProvincesGroupItem $item) use ($saleProvince) {
            return $item->getProvince()->getId() === $saleProvince->getId();
        });
    }

    private function isNotSaleMadeInASirtacProvince(WithholdingStageContext $stageContext): bool
    {
        return !$this->isSaleMadeInASirtacProvince($stageContext);
    }

    private function isSubjectInTaxRegistryAsRegistered(WithholdingStageContext $stageContext): bool
    {
        if ($this->isNotSubjectInTaxRegistry($stageContext)) {
            return false;
        }

        if (!$this->isSubjectRegisteredInJurisdiction($stageContext)) {
            return false;
        }

        return true;
    }

    private function isSubjectInTaxRegistryAsUnregistered(WithholdingStageContext $stageContext): bool
    {
        if ($this->isNotSubjectInTaxRegistry($stageContext)) {
            return false;
        }

        if ($this->isSubjectRegisteredInJurisdiction($stageContext)) {
            return false;
        }

        return true;
    }

    private function isSubjectRegisteredInJurisdiction(WithholdingStageContext $stageContext): bool
    {
        /** @var WithholdingTaxDynamicRule $applicableRule */
        $applicableRule = $stageContext->lastWithholdingMatchedRule();
        $statusJurisdictions = $applicableRule->getStatusJurisdictions();
        $saleProvinceId = $stageContext->getTransactionTaxingPointOfView()->province->getId();

        return SirtacJurisdictionsHelper::isRegisteredInJurisdiction($statusJurisdictions, $saleProvinceId);
    }

    private function isSubjectRegisteredInAnyProvince(WithholdingStageContext $stageContext): bool
    {
        $subjectTaxCategoryCode = (int) $stageContext->getLoggedTaxCategory()->getId();
        if (TaxCategoryCode::NO_INSCRIPTO === $subjectTaxCategoryCode) {
            return false;
        }

        return true;
    }

    private function isSubjectRegisteredAsExempt(WithholdingStageContext $stageContext): bool
    {
        $subjectTaxCategoryCode = (int) $stageContext->getLoggedTaxCategory()->getId();
        if (TaxCategoryCode::EXENTO !== $subjectTaxCategoryCode) {
            return false;
        }

        return true;
    }

    protected function generateLogText(WithholdingStageContext $stageContext, WithholdingTaxLog $log): string
    {
        $taxConcept = $log->getTaxDetail()->getConcept();
        /** @var Province $province */
        $province = $stageContext->getTransactionTaxingPointOfView()->province;
        switch ($taxConcept->getId()) {
            case TaxConceptEnum::WITHHOLDING_ID:
                $message = 'Retención Impuesto Ingresos Brutos SIRTAC';

                break;
            case TaxConceptEnum::INFORMATIVE_ID:
                $message = 'Retención Informativa por alicuota en padrón en 0';

                break;
            case TaxConceptEnum::UNREGISTERED_ID:
                $message = "Retención Impuesto Ingresos Brutos no inscripto [{$province->getName()}]";

                break;
            case TaxConceptEnum::PENALTY_ID:
                $message = "Retención Impuesto Ingresos Brutos por falta de alta [{$province->getName()}]";

                break;
            default:
                $message = '';

                break;
        }

        return $message;
    }
}
