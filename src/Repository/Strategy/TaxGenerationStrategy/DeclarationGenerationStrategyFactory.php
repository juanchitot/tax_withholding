<?php

namespace GeoPagos\WithholdingTaxBundle\Repository\Strategy\TaxGenerationStrategy;

use GeoPagos\ApiBundle\Contracts\ConfigurationManagerInterface;
use GeoPagos\WithholdingTaxBundle\Entity\ProvinceWithholdingTaxSetting;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;
use GeoPagos\WithholdingTaxBundle\Exceptions\TaxGenerationStrategyNotFound;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DeclarationGenerationStrategyFactory
{
    /** @var ServiceLocator */
    private $serviceLocator;

    /** @var ConfigurationManagerInterface */
    protected $configurationManager;

    public function __construct(ContainerInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function getDeclarationGenerationStrategy(ProvinceWithholdingTaxSetting $setting): DeclarationGenerationStrategy
    {
        $taxGenerationStrategy = null;

        $taxesUsingWithholdingTax = [
            WithholdingTaxTypeEnum::VAT,
            WithholdingTaxTypeEnum::INCOME_TAX,
            WithholdingTaxTypeEnum::TAX,
        ];

        $settingTax = $setting->getWithholdingTaxType();

        if (in_array($settingTax, $taxesUsingWithholdingTax)) {
            $taxGenerationStrategy = $this->serviceLocator->get(WithholdingTaxGenerator::class);
        }

        if (WithholdingTaxTypeEnum::SIRTAC === $settingTax) {
            $taxGenerationStrategy = $this->serviceLocator->get(SirtacDeclarationGenerator::class);
        }

        if (null === $taxGenerationStrategy) {
            throw new TaxGenerationStrategyNotFound($settingTax);
        }

        return $taxGenerationStrategy;
    }
}
