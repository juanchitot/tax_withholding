<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class CabaParser implements RuleFileParserInterface
{
    /**
     * @var WithholdingTaxRuleFile
     */
    private $ruleFile;

    /**
     * @var int
     */
    private $provinceIdentifier = null;

    public function __construct(WithholdingTaxRuleFile $ruleFile)
    {
        $this->ruleFile = $ruleFile;
    }

    public function parse(string $line): RuleLineDtoInterface
    {
        $dto = new RuleLineDto();
        $dto->taxPayer = substr($line, 27, 11);
        $dto->provinceIdentifier = $this->getProvinceIdentifier();
        $dto->rate = (float) str_replace(',', '.', substr($line, 50, 4));
        $dto->monthYear = substr($line, 20, 2).'-'.substr($line, 22, 4);
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

    public function skipFirstLine(): bool
    {
        return false;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::TAX_TYPE;
    }
}
