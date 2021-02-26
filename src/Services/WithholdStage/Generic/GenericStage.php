<?php

namespace GeoPagos\WithholdingTaxBundle\Services\WithholdStage\Generic;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdStageInterface;
use GeoPagos\WithholdingTaxBundle\Entity\TaxConcept;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDetail;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxLog;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Enum\TaxConceptEnum;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleAmountFieldEnum;
use GeoPagos\WithholdingTaxBundle\Helper\DiscretizationHelper;
use GeoPagos\WithholdingTaxBundle\Helper\WithholdCalculationsHelper;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxExclusionRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\Rules\RulesFacade;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\TaxingProvinceResolution;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\WithholdingTaxSettingsService;
use Psr\Log\LoggerInterface;

/**
 * Template Method for withholding tax.
 */
abstract class GenericStage implements WithholdStageInterface
{
    /** @var WithholdingTaxExclusionRepository */
    protected $withholdingTaxExclusionRepository;

    /** @var WithholdingTaxSettingsService */
    protected $withholdingTaxSettingsService;

    /** @var WithholdingTaxRuleRepository */
    protected $withholdingTaxRuleRepository;

    /** @var RulesFacade */
    protected $rulesFacade;

    /** @var LoggerInterface */
    protected $logger;

    /** @var TaxingProvinceResolution */
    private $taxingProvinceResolution;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ConfigurationManagerInterface */
    private $configurationManager;

    public function __construct(
        WithholdingTaxExclusionRepository $withholdingTaxExclusionRepository,
        WithholdingTaxSettingsService $withholdingTaxSettingsService,
        WithholdingTaxRuleRepository $withholdingTaxRuleRepository,
        TaxingProvinceResolution $taxingProvinceResolution,
        ConfigurationManagerInterface $configurationManager,
        RulesFacade $rulesFacade,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->withholdingTaxExclusionRepository = $withholdingTaxExclusionRepository;
        $this->withholdingTaxSettingsService = $withholdingTaxSettingsService;
        $this->withholdingTaxRuleRepository = $withholdingTaxRuleRepository;
        $this->taxingProvinceResolution = $taxingProvinceResolution;
        $this->configurationManager = $configurationManager;
        $this->rulesFacade = $rulesFacade;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function process(SaleBag $saleBag, Transaction $transaction): bool
    {
        $stageContext = $this->prepareContext($transaction);

        if (!$this->canTaxBeWithheld($stageContext)) {
            $this->log('Tax can\'t be withheld for the given context.', $stageContext);

            return false;
        }

        $baseRule = $this->findBaseRule($stageContext);
        if (!$this->appliesBaseRuleToSubject($stageContext, $baseRule)) {
            $this->log('No base rule found for the given context.', $stageContext);

            return false;
        }

        $stageContext->pushWithholdingMatchedRule($baseRule);
        $this->updateContextWithApplicableRule($stageContext, $saleBag);

        if ($this->taxMustNotInformEmptyRates() && $this->applicableRuleDoesntHaveRate($stageContext)) {
            $this->log('Applicable rate is 0 for the given context.', $stageContext);

            return false;
        }

        if (!$this->appliesTaxableMinimumAmount($stageContext, $saleBag)) {
            $this->log('Taxable minimum amount doesn\'t apply for the given context.', $stageContext);

            return false;
        }

        try {
            return $this->doWithholdingTax($stageContext, $saleBag, TaxConceptEnum::WITHHOLDING_ID);
        } catch (Exception $e) {
            $message = 'There was an error doing the withholding tax: '.$e->getMessage();
            $this->log($message, $stageContext);

            return false;
        }
    }

    protected function log(string $message, WithholdingStageContext $stageContext): void
    {
        $message = sprintf('[%s] '.$message.' %s ', $this->getTaxType(), (string) $stageContext);
        $this->logger->info($message);
    }

    // Variable Template Method behaviour

    abstract public static function getTaxType(): string;

    abstract protected function isSubjectConfiguredAsExcluded(WithholdingStageContext $stageContext): bool;

    abstract protected function findBaseRule(WithholdingStageContext $stageContext): ?WithholdingTaxRule;

    abstract protected function appliesTaxableMinimumAmount(
        WithholdingStageContext $stageContext,
        SaleBag $saleBag
    ): bool;

    abstract protected function taxMustNotInformEmptyRates(): bool;

    abstract protected function generateLogText(WithholdingStageContext $stageContext, WithholdingTaxLog $log): string;

    // END - Variable Template Method behaviour

    protected function updateContextWithApplicableRule(WithholdingStageContext $stageContext, SaleBag $saleBag): void
    {
        $this->rulesFacade->findDynamicRule($stageContext, $saleBag) ||
        $this->rulesFacade->findSimpleRule($stageContext) ||
        $this->rulesFacade->findHardRules($stageContext);
    }

    protected function applicableRuleDoesntHaveRate(WithholdingStageContext $stageContext): bool
    {
        return 0 == $stageContext->getApplicableRate();
    }

    protected function doWithholdingTax(
        WithholdingStageContext $stageContext,
        SaleBag $saleBag,
        int $taxConceptId
    ): bool {
        $transaction = $stageContext->getTransaction();

        $withholdingTaxDetail = $this->persistWithholdingTaxDetail(
            $stageContext,
            $transaction,
            $taxConceptId
        );

        $this->persistWithholdingTaxLog(
            $stageContext,
            $transaction,
            $withholdingTaxDetail
        );

        $saleBag->addWithheldTax(
            $this->getTaxType(),
            $withholdingTaxDetail->getSignedTaxableIncome(),
            $withholdingTaxDetail->getSignedAmount()
        );

        return true;
    }

    private function canTaxBeWithheld(WithholdingStageContext $stageContext): bool
    {
        if ($this->isSubjectInTheWithholdingTaxExclusionList($stageContext)) {
            return false;
        }

        if ($this->taxTypeDoesntHaveSystemToReport($stageContext)) {
            return false;
        }

        if ($this->isSubjectConfiguredAsExcluded($stageContext)) {
            return false;
        }

        return true;
    }

    private function isSubjectInTheWithholdingTaxExclusionList(WithholdingStageContext $stageContext): bool
    {
        $withholdingTaxExclusions = $this->withholdingTaxExclusionRepository->findExclusionsBy(
            $stageContext->getSubsidiary()
        );

        return count($withholdingTaxExclusions) > 0;
    }

    private function taxTypeDoesntHaveSystemToReport(WithholdingStageContext $stageContext): bool
    {
        return null === $this->withholdingTaxSettingsService->getProvinceWithholdingTaxSetting(
            $stageContext->getTransactionTaxingPointOfView()->province,
            $this->getTaxType()
        );
    }

    private function appliesBaseRuleToSubject(
        WithholdingStageContext $stageContext,
        ?WithholdingTaxRule $baseRule
    ): bool {
        if (null === $baseRule) {
            return false;
        }

        if (($stageContext->getSubsidiary()->isOccasional() && !$baseRule->getWithholdOccasional())) {
            return false;
        }

        return true;
    }

    protected function persistWithholdingTaxDetail(
        WithholdingStageContext $stageContext,
        Transaction $transaction,
        int $taxConceptCode
    ): WithholdingTaxDetail {
        /** @var WithholdingTaxRule $withholdingTaxRule */
        $withholdingTaxRule = $stageContext->firstWithholdingMatchedRule();

        $amount = WithholdCalculationsHelper::getTaxableAmountFromRule(
            $transaction,
            $withholdingTaxRule,
            $stageContext->getTaxableIncomeCoefficient()
        );

        if ($this->configurationManager->isFeatureEnabled('promotions.process_adjustments')) {
            $amount += $this->getTransactionAdjustmentsAmount($transaction, $withholdingTaxRule->getCalculationBasis());
        }

        $withholdingTaxDetail = $this->entityManager->getRepository(WithholdingTaxDetail::class)->findOneBy([
            'transaction' => $transaction,
            'type' => $this->getTaxType(),
        ]);

        if (!$withholdingTaxDetail) {
            /** @var TaxConcept $taxConceptReference */
            $taxConceptReference = $this->entityManager->getReference(
                TaxConcept::class,
                $taxConceptCode
            );
            $withholdingTaxDetail = new WithholdingTaxDetail();
            $withholdingTaxDetail
                ->setTransaction($transaction)
                ->setType($this->getTaxType())
                ->setConcept($taxConceptReference);
        }

        $amountWithheld = DiscretizationHelper::getRatePartFromAmount(
            $stageContext->getApplicableRate(),
            $amount
        );

        $withholdingTaxDetail
            ->setAmount($amountWithheld)
            ->setTaxableIncome($amount)
            ->setRate($stageContext->getApplicableRate());

        $this->entityManager->persist($withholdingTaxDetail);

        $transaction->addWithholdingTaxDetails($withholdingTaxDetail);

        return $withholdingTaxDetail;
    }

    protected function persistWithholdingTaxLog(
        WithholdingStageContext $stageContext,
        Transaction $transaction,
        WithholdingTaxDetail $withholdingTaxDetail
    ): void {
        $log = $this->entityManager->getRepository(WithholdingTaxLog::class)->findOneBy([
            'transaction' => $transaction,
            'taxDetail' => $withholdingTaxDetail,
        ]);

        if (!$log) {
            $pov = $stageContext->getTransactionTaxingPointOfView();
            $log = (new WithholdingTaxLog($transaction, $withholdingTaxDetail, $pov->province));
            $this->entityManager->persist($log);
        }

        $log
            ->setTaxCategory($stageContext->getLoggedTaxCategory())
            ->setTaxCondition($stageContext->getSubsidiary()->getTaxCondition())
            ->setRuleApplied($this->generateLogText($stageContext, $log));
    }

    protected function prepareContext(Transaction $transaction): WithholdingStageContext
    {
        $pov = $this->taxingProvinceResolution->transactionTaxingPointOfView(
            $transaction,
            $this->getTaxType()
        );

        $stageContext = new WithholdingStageContext($transaction);

        $stageContext
            ->setTaxType($this->getTaxType())
            ->setTransactionTaxingPointOfView($pov)
            ->setLoggedTaxCategory($stageContext->getSubsidiary()->getTaxCategory());

        return $stageContext;
    }

    private function getTransactionAdjustmentsAmount(Transaction $transaction, string $calculationBasis): int
    {
        $adjustmentTypes = $this->getAdjustmentTypesCriteria($calculationBasis);
        if (empty($adjustmentTypes)) {
            return 0;
        }

        $amount = 0;
        $adjustmentTransactions = $this->getTransactionsByAdjustmentTypes($transaction, $adjustmentTypes);
        foreach ($adjustmentTransactions as $adjustmentTransaction) {
            /* @var Transaction $adjustmentTransaction */
            $amount += $adjustmentTransaction->getAmount();
        }

        return $amount;
    }

    private function getAdjustmentTypesCriteria(string $calculationBasis): array
    {
        $adjustmentTypes = [];

        if (WithholdingTaxRuleAmountFieldEnum::NET_STRING === $calculationBasis) {
            $adjustmentTypes = [
                Transaction::WITHHOLD_ADJUSTMENT_NET,
                Transaction::WITHHOLD_ADJUSTMENT_GROSS,
            ];
        } elseif (WithholdingTaxRuleAmountFieldEnum::GROSS_STRING === $calculationBasis) {
            $adjustmentTypes = [
                Transaction::WITHHOLD_ADJUSTMENT_GROSS,
            ];
        }

        return $adjustmentTypes;
    }

    private function getTransactionsByAdjustmentTypes(Transaction $transaction, array $adjustmentTypes): array
    {
        $transactionRepository = $this->entityManager->getRepository(Transaction::class);

        return $transactionRepository->findBy([
            'transactionId' => $transaction->getId(),
            'typeId' => $adjustmentTypes,
        ]);
    }
}
