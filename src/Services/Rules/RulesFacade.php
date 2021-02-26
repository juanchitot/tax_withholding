<?php

namespace GeoPagos\WithholdingTaxBundle\Services\Rules;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Entity\PaymentMethod;
use GeoPagos\ApiBundle\Entity\PaymentMethodType;
use GeoPagos\ApiBundle\Entity\Province;
use GeoPagos\ApiBundle\Entity\Subsidiary;
use GeoPagos\ApiBundle\Entity\TaxCategory;
use GeoPagos\ApiBundle\Entity\Transaction;
use GeoPagos\ApiBundle\Enum\TaxCategoryCode;
use GeoPagos\ApiBundle\Repository\TaxCategoryRepository;
use GeoPagos\WithholdingTaxBundle\Contract\RulesFacadeInterface;
use GeoPagos\WithholdingTaxBundle\Contract\WithholdingTaxRuleInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxRuleFileStatus;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Model\Sale\SaleBag;
use GeoPagos\WithholdingTaxBundle\Model\WithholdingStageContext;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxCategoryPerProvinceRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxDynamicRuleRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxHardRuleRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxSimpleRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\HabitualsService;
use GeoPagos\WithholdingTaxBundle\Services\WithholdingTax\TaxingProvinceResolution;
use Psr\Log\LoggerInterface;

final class RulesFacade implements RulesFacadeInterface
{
    /** @var WithholdingTaxDynamicRuleRepository */
    private $withholdingTaxDynamicRuleRepository;

    /** @var WithholdingTaxSimpleRuleRepository */
    private $withholdingTaxSimpleRuleRepository;

    /** @var WithholdingTaxHardRuleRepository */
    private $withholdingTaxHardRuleRepository;

    /** @var HabitualsService */
    private $habitualsService;

    /** @var TaxingProvinceResolution */
    private $taxingProvinceResolution;

    /** @var TaxCategoryRepository */
    private $taxCategoryRepository;

    /** @var WithholdingTaxCategoryPerProvinceRepository */
    private $withholdingTaxCategoryPerProvinceRespository;

    /** @var LoggerInterface */
    private $logger;

    /** @var EntityManagerInterface */
    private $em;

    /** @var array */
    private $dynamicRulesLoaded;

    /** @var array */
    private $withholdingTaxCategoriesPerProvinceCache;

    /** @var PaymentMethod */
    private $debitPaymentMethod;

    /* @var TaxCategory */
    private $loggedTaxCategory = null;

    public function __construct(
        WithholdingTaxDynamicRuleRepository $withholdingTaxDynamicRuleRepository,
        WithholdingTaxSimpleRuleRepository $withholdingTaxSimpleRuleRepository,
        WithholdingTaxHardRuleRepository $withholdingTaxHardRuleRepository,
        HabitualsService $habitualsService,
        TaxingProvinceResolution $taxingProvinceResolution,
        TaxCategoryRepository $taxCategoryRepository,
        WithholdingTaxCategoryPerProvinceRepository $withholdingTaxCategoryPerProvinceRepository,
        LoggerInterface $logger,
        EntityManagerInterface $em
    ) {
        $this->withholdingTaxDynamicRuleRepository = $withholdingTaxDynamicRuleRepository;
        $this->withholdingTaxSimpleRuleRepository = $withholdingTaxSimpleRuleRepository;
        $this->withholdingTaxHardRuleRepository = $withholdingTaxHardRuleRepository;
        $this->habitualsService = $habitualsService;
        $this->taxingProvinceResolution = $taxingProvinceResolution;
        $this->taxCategoryRepository = $taxCategoryRepository;
        $this->withholdingTaxCategoryPerProvinceRespository = $withholdingTaxCategoryPerProvinceRepository;
        $this->logger = $logger;
        $this->em = $em;

        $this->withholdingTaxCategoriesPerProvinceCache = [];
        $this->debitPaymentMethod = $this->em->getRepository(PaymentMethodType::class)->findOneBy([
            'type' => PaymentMethod::TYPE_DEBIT, ]
        );
    }

    public function findDynamicRule(WithholdingStageContext $context, SaleBag $saleBag): ?WithholdingTaxRuleInterface
    {
        $dynamicRule = null;

        /* @var $withholdingTaxRule WithholdingTaxRule */
        $withholdingTaxRule = $context->firstWithholdingMatchedRule();
        if ($withholdingTaxRule && $withholdingTaxRule->hasTaxRegistry()) {
            $taxType = (string) $context->getTaxType();
            $dynamicRule = $this->withholdingTaxDynamicRuleRepository->getRuleByType(
                $taxType,
                $context->getAccount()->getIdFiscal(),
                $context->getTransactionTaxingPointOfView()->province,
                $saleBag->getAvailableDate()
            );
        }

        if ($dynamicRule) {
            $context->pushWithholdingMatchedRule($dynamicRule);
            $context->setApplicableRate($dynamicRule->getRate());
        }

        return $dynamicRule;
    }

    public function findSimpleRule(WithholdingStageContext $context): ?WithholdingTaxRuleInterface
    {
        /** @var WithholdingTaxSimpleRule */
        $simpleRuleParams = $this->getSimpleRuleParams(
            $context->getTransaction(),
            $context->getSubsidiary(),
            $context->getTaxType()
        );

        if (!$simpleRuleParams) {
            return null;
        }

        $simpleRule = $this->withholdingTaxSimpleRuleRepository->findByWithholdingTaxParams(
            $simpleRuleParams,
            $context->getTaxType()
        );

        if ($simpleRule && ($simpleRule->getMinimunAmount() > 0 || $this->haveAmountInsidePeriod($context))) {
            $context->pushWithholdingMatchedRule($simpleRule);
            $context->setApplicableRate($simpleRule->getRate());
            $context->setTaxableIncomeCoefficient($simpleRule->getTaxableAmountCoefficient());

            return $simpleRule;
        }

        return null;
    }

    private function haveAmountInsidePeriod(WithholdingStageContext $context): ?bool
    {
        return (bool) $this->em->getRepository(Transaction::class)->findBySubsidiaryIdAndPeriod(
            $context->getSubsidiary(),
            $context->firstWithholdingMatchedRule()
        );
    }

    private function getSimpleRuleParams(Transaction $transaction, Subsidiary $subsidiary, $type)
    {
        if (WithholdingTaxTypeEnum::TAX == $type || WithholdingTaxTypeEnum::SIRTAC == $type) {
            return $this->getTaxSimpleRuleParams($transaction, $subsidiary, $type);
        }

        if (null == $transaction->getTransactionDetail()->getPaymentMethod()) {
            // This situation comes from a adjustment transaction, so now we are skipping thems as simple rules.
            return false;
        }

        $classificationId = null;

        if ($subsidiary && $subsidiary->getAccount()->getClassification()) {
            $classificationId = $subsidiary->getAccount()->getClassification()->getId();
        }

        $paymentType = $transaction->getTransactionDetail()->getPaymentMethod()->getType();

        // in this case is the same as DEBIT
        if (PaymentMethod::ACCOUNT === $paymentType->getName()) {
            $paymentType = $this->debitPaymentMethod;
        }

        $returnValue = [
            'classificationId' => $classificationId,
            'paymentMethodType' => $paymentType,
        ];

        if ($subsidiary->getTaxCondition() && (WithholdingTaxTypeEnum::ITBIS != $type)) {
            $returnValue['taxConditionId'] = $subsidiary->getTaxCondition()->getId();
        }

        return $returnValue;
    }

    private function getTaxSimpleRuleParams(Transaction $transaction, Subsidiary $subsidiary, $type)
    {
        $pov = $this->taxingProvinceResolution->transactionTaxingPointOfView(
            $transaction,
            $type
        );

        if (!$pov->useBuyerProvince || $pov->province->getId() == $subsidiary->getProvince()->getId()) {
            $this->setLoggedTaxCategory($subsidiary->getTaxCategory());

            return [
                'provinceId' => $pov->province->getId(),
                'taxCategoryId' => $subsidiary->getTaxCategory()->getId(),
            ];
        }

        $categoryOfSubsidiaryInProvinceOfBuyer = $this->getTaxCategoryPerProvinceFromCache($subsidiary, $pov->province);
        if (null === $categoryOfSubsidiaryInProvinceOfBuyer) {
            $this->setLoggedTaxCategory($this->taxCategoryRepository->findBufferedById(TaxCategoryCode::NO_INSCRIPTO));

            return [
                'provinceId' => $pov->province->getId(),
                'taxCategoryId' => $this->taxCategoryRepository->findBufferedById(TaxCategoryCode::NO_INSCRIPTO)->getId(),
            ];
        }

        $this->setLoggedTaxCategory($categoryOfSubsidiaryInProvinceOfBuyer->getTaxCategory());

        return [
            'provinceId' => $categoryOfSubsidiaryInProvinceOfBuyer->getProvince()->getId(),
            'taxCategoryId' => $categoryOfSubsidiaryInProvinceOfBuyer->getTaxCategory()->getId(),
        ];
    }

    private function getTaxCategoryPerProvinceFromCache(Subsidiary $subsidiary, Province $province)
    {
        $subsidiaryId = $subsidiary->getId();
        $provinceId = $province->getId();
        if (!isset($this->withholdingTaxCategoriesPerProvinceCache[$subsidiaryId][$provinceId])) {
            $this->withholdingTaxCategoriesPerProvinceCache[$subsidiaryId][$provinceId] =
                $this->withholdingTaxCategoryPerProvinceRespository->findOneBySubsidiaryAndProvince(
                    $subsidiary,
                    $province
                );
        }

        return $this->withholdingTaxCategoriesPerProvinceCache[$subsidiaryId][$provinceId];
    }

    public function findHardRules(WithholdingStageContext $context): ?WithholdingTaxRuleInterface
    {
        if ($this->shouldUseUnpuslishRate($context)) {
            // La provincia usa padron, no esta cargado el del mes y
            // existe un valor para los no publicados en el padron
            $context->setApplicableRate($context->firstWithholdingMatchedRule()->getUnpublishRate());

            return $context->firstWithholdingMatchedRule();
        }

        $withholdingTaxRule = $context->firstWithholdingMatchedRule();
        /** @var WithholdingTaxHardRule */
        $hardRule = $this->withholdingTaxHardRuleRepository->findFirstBufferedHardRuleFromBaseRule($withholdingTaxRule);

        $isHabitual = $this->isSubjectMarkedAsHabitual($context);

        if ($hardRule && ($isHabitual || $this->doesRuleApply(
            $context->getSubsidiary(),
            $hardRule,
            $context->getTransaction(),
            $context->getTaxType()
        ))) {
            $context->setApplicableRate($hardRule->getRate());

            if (!$isHabitual) {
                $this->markSubjectAsHabitual($context);
            }

            return $hardRule;
        }

        return null;
    }

    public function isSubjectMarkedAsHabitual(WithholdingStageContext $context): bool
    {
        return $this->habitualsService->isSubjectMarkedAsHabitual(
            $context->getSubsidiary(),
            $context->getTaxType(),
            $context->getTransactionTaxingPointOfView()->province
        );
    }

    public function markSubjectAsHabitual(WithholdingStageContext $context): void
    {
        $this->habitualsService->markSubjectAsHabitual(
            $context->getSubsidiary(),
            $context->getTaxType(),
            $context->getTransactionTaxingPointOfView()->province
        );
    }

    public function shouldUseUnpuslishRate(WithholdingStageContext $context): bool
    {
        return
            WithholdingTaxTypeEnum::TAX === $context->getTaxType()
            && $context->firstWithholdingMatchedRule()->getUnpublishRate() > 0
            && $context->firstWithholdingMatchedRule()->hasTaxRegistry()
            && !$this->thereAreDynamicRules(
                $context->firstWithholdingMatchedRule(),
                $context->getTransaction()->getAvailableDate()
            )
        ;
    }

    private function thereAreDynamicRules(WithholdingTaxRuleInterface $withholdingTaxRule, Carbon $availableDate)
    {
        $key = $this->getDynamicRuleKey($withholdingTaxRule, $availableDate);

        if (isset($this->dynamicRulesLoaded[$key])) {
            return $this->dynamicRulesLoaded[$key];
        }

        $rtnValue = false;
        if (WithholdingTaxTypeEnum::TAX == $withholdingTaxRule->getType()) {
            $file = $this->em->getRepository(WithholdingTaxRuleFile::class)->findOneBy([
                'province' => $withholdingTaxRule->getProvince(),
                'date' => $availableDate->format('m-Y'),
                'fileType' => WithholdingTaxRuleFile::GROSS_INCOME_TYPE,
                'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            ]);
            if ($file && $file->getImported() > 0) {
                $rtnValue = true;
            }
        } else {
            $file = $this->em->getRepository(WithholdingTaxRuleFile::class)->findOneBy([
                'date' => $availableDate->format('m-Y'),
                'fileType' => WithholdingTaxRuleFile::MICRO_ENTERPRISE,
                'status' => WithholdingTaxRuleFileStatus::SUCCESS,
            ]);
            if ($file && $file->getImported() > 0) {
                $rtnValue = true;
            }
        }

        $this->dynamicRulesLoaded[$key] = $rtnValue;

        return $this->dynamicRulesLoaded[$key];
    }

    private function getDynamicRuleKey(WithholdingTaxRuleInterface $withholdingTaxRule, Carbon $availableDate)
    {
        $rtnValue = $withholdingTaxRule->getType();
        if (WithholdingTaxTypeEnum::TAX == $withholdingTaxRule->getType()) {
            $rtnValue .= $withholdingTaxRule->getProvince()->getAcronym();
        }

        return $rtnValue.$availableDate->format('Ymd');
    }

    private function doesRuleApply(
        Subsidiary $subsidiary,
        WithholdingTaxHardRule $hardRule,
        Transaction $transaction,
        $taxType
    ): bool {
        if ($hardRule->shouldApplyToday()) {
            $pov = $this->taxingProvinceResolution->transactionTaxingPointOfView(
                $transaction,
                $taxType
            );

            list($transactions, $rules) = $this->em->getRepository(Transaction::class)
                ->findByHardRules($subsidiary, $hardRule, $taxType, $pov->useBuyerProvince);

            if ($this->checkRules($transactions, $rules)) {
                return true;
            }
        }

        return false;
    }

    private function checkRules($transactions, $rules): bool
    {
        $transactionVerifier = count($transactions);
        $transactionCounter = 0;
        $condition = '';

        try {
            foreach ($rules as $rule) {
                if (is_null($transactions[$rule->field])) {
                    $transactions[$rule->field] = 0;
                }
                $condition = 'return '.$transactions[$rule->field].' '.$rule->condition." '".$rule->value."';";

                $condition = eval($condition);

                if ($condition) {
                    ++$transactionCounter;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Eval condition wrong in getWithholdingTaxHardRule function. '".$condition."'");
            $this->logger->error('Original Msg: '.$e->getMessage());

            return false;
        }
        if ($transactionVerifier != $transactionCounter) {
            return false;
        }

        return true;
    }

    private function setLoggedTaxCategory(TaxCategory $loggedTaxCategory = null)
    {
        $this->loggedTaxCategory = $loggedTaxCategory;
    }

    public function getLoggedTaxCategory(): ?TaxCategory
    {
        return $this->loggedTaxCategory;
    }
}
