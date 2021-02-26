<?php

namespace GeoPagos\WithholdingTaxBundle\Controller\v2;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\ApiBundle\Services\Configurations\ConfigurationManager;
use GeoPagos\CommonBackBundle\Controller\BackOfficeController;
use GeoPagos\CommonBackBundle\Helper\BreadcrumbHelper;
use GeoPagos\CommonBackBundle\Model\StaticConstant;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxHardRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRule;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxSimpleRule;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Helper\DiscretizationHelper;
use GeoPagos\WithholdingTaxBundle\Model\Dto\ExcludedRateDto;
use GeoPagos\WithholdingTaxBundle\Model\Dto\RateByCategoryDto;
use GeoPagos\WithholdingTaxBundle\Model\Dto\RateByConditionDto;
use GeoPagos\WithholdingTaxBundle\Model\Dto\RateForHabitualDto;
use GeoPagos\WithholdingTaxBundle\Model\Dto\RuleDto;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxHardRuleRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxRuleRepository;
use GeoPagos\WithholdingTaxBundle\Repository\WithholdingTaxSimpleRuleRepository;
use GeoPagos\WithholdingTaxBundle\Services\EnabledTaxesInProjectTrait;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithholdingTaxRuleController extends BackOfficeController
{
    use EnabledTaxesInProjectTrait;

    private const MODULE_NAME = 'withholding_tax_rule';

    private const FEDERAL_TAX_TYPES = [
        WithholdingTaxTypeEnum::INCOME_TAX,
        WithholdingTaxTypeEnum::VAT,
        WithholdingTaxTypeEnum::ITBIS,
    ];

    /** @var WithholdingTaxRuleRepository */
    private $rulesRepository;

    /** @var WithholdingTaxSimpleRuleRepository */
    private $simpleRulesRepository;

    /** @var WithholdingTaxHardRuleRepository */
    private $hardRuleRepository;

    public function __construct(
        TranslatorInterface $translator,
        EntityManagerInterface $entityManager,
        ConfigurationManager $configurationManager,
        WithholdingTaxRuleRepository $rulesRepository,
        WithholdingTaxSimpleRuleRepository $simpleRulesRepository,
        WithholdingTaxHardRuleRepository $hardRuleRepository
    ) {
        parent::__construct($translator, $entityManager, $configurationManager);
        $this->rulesRepository = $rulesRepository;
        $this->simpleRulesRepository = $simpleRulesRepository;
        $this->hardRuleRepository = $hardRuleRepository;
    }

    public function getControllerBreadcrumb()
    {
        $breadcrumbHelper = new BreadcrumbHelper();

        $pageTitle = $this->translator->trans('withholding_tax.rules.index.title');
        $indexRoute = $this->generateUrl('withholding_tax_rule_backoffice_section');

        $breadcrumbHelper->addPage(
            $pageTitle,
            $indexRoute
        );

        return $breadcrumbHelper;
    }

    public function indexAction()
    {
        $this->getAuthorizer()->checkAuthorize(
            self::MODULE_NAME,
            StaticConstant::ROLE_ACTION_VIEW
        );

        $enabledRules = $this->rulesRepository->getRulesSortedByTaxTypeAndProvince(
            $this->getEnabledTaxesInProject($this->configurationManager)
        );

        $enabledRulesDto = $this->convertWithholdingTaxRulesToRulesDto($enabledRules);

        $twigData = array_merge($this->getParametersForView(), [
            'breadcrumb' => $this->getControllerBreadcrumb()->getBreadcrumb(),
            'rulesDto' => $enabledRulesDto,
            'enabledProvinces' => $this->getEnabledProvinces($enabledRulesDto),
        ]);

        $response = $this->render(
            '@GeoPagosWithholdingTax/WithholdingTaxRule/v2/index.html.twig',
            $twigData
        );

        $sectionCookie = new Cookie('wtr_version', 'v2');
        $response->headers->setCookie($sectionCookie);

        return $response;
    }

    /**
     * @param RuleDto[] $rules
     */
    private function getEnabledProvinces(array $rules): array
    {
        $provinceIds = array_column($rules, 'provinceId');
        $provinceNames = array_column($rules, 'provinceName');

        return array_filter(array_unique(array_combine($provinceIds, $provinceNames)));
    }

    /**
     * @param WithholdingTaxRule[] $withholdingTaxRules
     *
     * @return RuleDto[]
     */
    private function convertWithholdingTaxRulesToRulesDto(array $withholdingTaxRules): array
    {
        $rulesDto = [];
        foreach ($withholdingTaxRules as $withholdingTaxRule) {
            $ruleDto = new RuleDto();
            $ruleDto->taxType = $withholdingTaxRule->getType();
            $ruleDto->calculationBasis = $withholdingTaxRule->getCalculationBasisAsString();

            $ruleDto->hasTaxRegistry = (bool) $withholdingTaxRule->hasTaxRegistry();
            if ($ruleDto->hasTaxRegistry) {
                $ruleDto->unpublishedRate = DiscretizationHelper::truncate(
                    $withholdingTaxRule->getUnpublishRate()
                );
                $ruleDto->publishedMinimumAmount = DiscretizationHelper::truncate(
                    $withholdingTaxRule->getMinimumDynamicRuleAmount()
                );
            }

            $ruleDto->taxIsFederal = (bool) in_array($ruleDto->taxType, self::FEDERAL_TAX_TYPES);
            if ($ruleDto->taxIsFederal) {
                $ruleDto->provinceId = $ruleDto->provinceName = null;
                list($ratesDto, $excludedRatesDto) = $this->getRatesByConditionDto($withholdingTaxRule);
                $ruleDto->ratesByCondition = $ratesDto;
                $ruleDto->excludedRates = $excludedRatesDto;
            } else {
                $ruleDto->provinceId = $withholdingTaxRule->getProvince()->getId();
                $ruleDto->provinceName = $withholdingTaxRule->getProvince()->getName();
                list($ratesDto, $excludedRatesDto) = $this->getRatesByCategoryDto($withholdingTaxRule);
                $ruleDto->ratesByCategory = $ratesDto;
                $ruleDto->excludedRates = $excludedRatesDto;
            }

            $ruleDto->ratesForHabituals = $this->getRatesForHabitualsDto($withholdingTaxRule);
            $rulesDto[] = $ruleDto;
        }

        return $rulesDto;
    }

    private function getRatesByCategoryDto(WithholdingTaxRule $withholdingTaxRule): array
    {
        $ratesDto = [];
        $excludedRatesDto = [];

        $simpleRules = $this->simpleRulesRepository->findRulesByProvincialTaxRule($withholdingTaxRule);
        foreach ($simpleRules as $simpleRule) {
            if ($simpleRule->getRate() > 0) {
                $rateByCategoryDto = new RateByCategoryDto();
                $rateByCategoryDto->category = $simpleRule->getTaxCategory()->getName();
                $rateByCategoryDto->taxableAmountCoefficient = $this->getTaxableAmountCoefficient($simpleRule);
                $rateByCategoryDto->minimumAmount = DiscretizationHelper::truncate(
                    $simpleRule->getMinimunAmount()
                );
                $rateByCategoryDto->rate = DiscretizationHelper::truncate(
                    $simpleRule->getRate()
                );
                $ratesDto[] = $rateByCategoryDto;
            } else {
                $excludedRatesDto[] = $this->getExcludedRateDto($simpleRule);
            }
        }

        return [$ratesDto, array_unique($excludedRatesDto, SORT_REGULAR)];
    }

    private function getExcludedRateDto(WithholdingTaxSimpleRule $simpleRule): ExcludedRateDto
    {
        $excludedRateDto = new ExcludedRateDto();
        if (null !== $simpleRule->getTaxCategory()) {
            $excludedRateDto->name = $simpleRule->getTaxCategory()->getName();
        } else {
            $excludedRateDto->name = $simpleRule->getTaxCondition()->getName();
        }

        return $excludedRateDto;
    }

    private function getRatesByConditionDto(WithholdingTaxRule $withholdingTaxRule): array
    {
        $ratesDto = [];
        $excludedRatesDto = [];

        $simpleRules = $this->simpleRulesRepository->findRulesByFederalTaxRule($withholdingTaxRule);
        foreach ($simpleRules as $simpleRule) {
            if ($simpleRule->getRate() > 0) {
                $rateByConditionDto = new RateByConditionDto();
                $rateByConditionDto->condition = null;
                if (null !== $simpleRule->getTaxCondition()) {
                    $rateByConditionDto->condition = $simpleRule->getTaxCondition()->getName();
                }
                $rateByConditionDto->rate = DiscretizationHelper::truncate(
                    $simpleRule->getRate()
                );
                $rateByConditionDto->minimumAmount = DiscretizationHelper::truncate(
                    $simpleRule->getMinimunAmount()
                );
                $rateByConditionDto->taxableAmountCoefficient = $this->getTaxableAmountCoefficient($simpleRule);
                $rateByConditionDto->paymentMethodType = null;
                $rateByConditionDto->paymentMethodType = $simpleRule->getPaymentMethodType();
                $rateByConditionDto->businessActivity = null;
                if (null !== $simpleRule->getClassification()) {
                    $rateByConditionDto->businessActivity = $simpleRule->getClassification()->getName();
                }
                $ratesDto[] = $rateByConditionDto;
            } else {
                $excludedRatesDto[] = $this->getExcludedRateDto($simpleRule);
            }
        }

        return [$ratesDto,  array_unique($excludedRatesDto, SORT_REGULAR)];
    }

    private function getTaxableAmountCoefficient(WithholdingTaxSimpleRule $simpleRule): ?float
    {
        if (1 !== $simpleRule->getTaxableAmountCoefficient()) {
            return $simpleRule->getTaxableAmountCoefficient() * 100;
        }

        return null;
    }

    /**
     * @return RateForHabitualDto[]
     */
    private function getRatesForHabitualsDto(WithholdingTaxRule $withholdingTaxRule): array
    {
        $ratesDto = [];
        $hardRules = $this->hardRuleRepository->findBy([
            'withholdingTaxRule' => $withholdingTaxRule,
        ], [
            'createdAt' => 'ASC',
        ]);

        foreach ($hardRules as $hardRule) {
            $rateForHabitualDto = new RateForHabitualDto();
            $rateForHabitualDto->minimumAmount = DiscretizationHelper::truncate(
                $hardRule->getMinimunAmount()
            );
            $rateForHabitualDto->rate = DiscretizationHelper::truncate(
                $hardRule->getRate()
            );
            $this->parseHardRuleFields($hardRule, $rateForHabitualDto);
            $ratesDto[] = $rateForHabitualDto;
        }

        return $ratesDto;
    }

    private function parseHardRuleFields(WithholdingTaxHardRule $hardRule, RateForHabitualDto $rateForHabitualDto): void
    {
        $constraints = json_decode($hardRule->getRule());
        $rateForHabitualDto->minimumTransactions = $rateForHabitualDto->limit = null;
        foreach ($constraints as $constraint) {
            if ('count' === $constraint->field) {
                $rateForHabitualDto->minimumTransactions = $constraint->value;
            }
            if ('amount' === $constraint->field) {
                $rateForHabitualDto->limit = $constraint->value;
            }
            if (null !== $rateForHabitualDto->minimumTransactions && null !== $rateForHabitualDto->limit) {
                break;
            }
        }
    }
}
