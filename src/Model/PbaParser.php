<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxDynamicRuleProvinceRate;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class PbaParser implements RuleFileParserInterface
{
    /**
     * @var WithholdingTaxRuleFile
     */
    private $ruleFile;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var int
     */
    private $provinceIdentifier = null;

    /**
     * @var array
     */
    private $rates = [];

    public function __construct(WithholdingTaxRuleFile $ruleFile, EntityManagerInterface $entityManager)
    {
        $this->ruleFile = $ruleFile;
        $this->entityManager = $entityManager;
    }

    public function parse(string $line): RuleLineDtoInterface
    {
        $dto = new RuleLineDto();
        $dto->taxPayer = substr($line, 2, 11);
        $dto->provinceIdentifier = $this->getProvinceIdentifier();
        $dto->rate = $this->getRate($line);
        $dto->monthYear = substr($line, 58, 2).'-'.substr($line, 60, 4);
        $dto->withholdingTaxType = $this->getTaxTypeForDynamicRule();

        return $dto;
    }

    private function getProvinceIdentifier()
    {
        if (null === $this->provinceIdentifier) {
            $this->provinceIdentifier = $this->ruleFile->getProvince()->getId();
        }

        return $this->provinceIdentifier;
    }

    private function getRate(string $line): float
    {
        if (count($this->rates) < 1) {
            $this->rates = $this->entityManager
                ->getRepository(WithholdingTaxDynamicRuleProvinceRate::class)
                ->findAllRates($this->ruleFile->getProvince());
        }

        $externalId = substr($line, 57, 1);

        return $this->rates[$externalId];
    }

    public function skipFirstLine(): bool
    {
        return false;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::TAX_TYPE;
    }
}
