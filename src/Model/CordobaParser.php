<?php

namespace GeoPagos\WithholdingTaxBundle\Model;

use GeoPagos\WithholdingTaxBundle\Contract\RuleFileParserInterface;
use GeoPagos\WithholdingTaxBundle\Contract\RuleLineDtoInterface;
use GeoPagos\WithholdingTaxBundle\Entity\WithholdingTaxRuleFile;
use GeoPagos\WithholdingTaxBundle\Enum\WithholdingTaxTypeEnum;

class CordobaParser implements RuleFileParserInterface
{
    private const DELIMITER = ';';

    /**
     * @var WithholdingTaxRuleFile
     */
    private $ruleFile;

    public function __construct(WithholdingTaxRuleFile $ruleFile)
    {
        $this->ruleFile = $ruleFile;
    }

    public function parse(string $line): RuleLineDtoInterface
    {
        $dto = new RuleLineDto();
        $data = str_getcsv($line, self::DELIMITER);

        $dto->taxPayer = $data[4];
        $dto->provinceIdentifier = $this->getProvinceIdentifier();
        $dto->rate = (float) $data[8];
        $dto->monthYear = substr($data[2], 2, 2).'-'.substr($data[2], -4);
        $dto->withholdingTaxType = $this->getTaxTypeForDynamicRule();

        return $dto;
    }

    public function skipFirstLine(): bool
    {
        return false;
    }

    public function getTaxTypeForDynamicRule(): int
    {
        return WithholdingTaxTypeEnum::TAX_TYPE;
    }

    private function getProvinceIdentifier()
    {
        return $this->ruleFile->getProvince()->getId();
    }
}
